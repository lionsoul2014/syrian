<?php
/**
 * PMController process manager.
 * @Note: start from 2016/06/22
 * We need a unified manager to manage all the running cli script extends from Cli_Controller
 * 1, start them together
 * 2, close them together
 * 3, monitor the all the script if anyone of them is down restart it
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

class PmController extends Cli_Controller
{
    private $debug;

    public function __before($input, $output, $uri)
    {
        parent::__before($input, $output, $uri);

        $this->debug = $input->getBoolean('debug', false);
        $taskFile = $input->get('taskFile');
        if ( $taskFile == false ) {
            echo "Usage: sudo php server.php /cli/pm/@action?taskFile=xxxxx.json\n";
            exit();
        }

        //@Note: make sure we run this under with root privileges
        $cuid  = posix_getuid();
        $rinfo = posix_getpwnam('root');
        if ( $rinfo == false || $cuid != $rinfo['uid'] ) {
            echo "Usage: sudo php server.php /cli/pm/@action?taskFile=xxxxx.json\n";
            exit();
        }

        if ( ! file_exists($taskFile) ) {
            echo "Error: Invalid cmd file path {$taskFile}\n";
            exit();
        }

        //---------------------------------------------
        //@Note: load and parse the task file
        $handler = fopen($taskFile, 'r');
        if ( $handler == FALSE ) {
            throw new Exception("Cannot open file {$taskFile}\n");
        }

        $buff = array();
        $buff[] = '{';
        while ( ! feof($handler) ) {
            $line = trim(fgets($handler, 4096));
            if ( strlen($line) < 1 ) continue;
            if ( $line[0] == '#' )   continue;
            $buff[] = $line;
        }
        $buff[] = '}';
        fclose($handler);

        $CC = implode('', $buff);
        if ( ($json = json_decode($CC)) == NULL ) {
            echo "Error: {$taskFile} is not file with correct json syntax\n";
            exit();
        }


        //record the root user id
        $this->rootUid   = posix_getuid();
        $this->taskFile  = $taskFile;
        $this->monitor   = $input->getBoolean('monitor', true);
        $this->logFile   = $input->get('logFile');

        # @Note: added at 2016/06/23
        # we define this cuz in the shell_exec process posix_getlogin
        # will not return the valid login user name
        $this->loginUser = $input->get('loginUser', NULL, posix_getlogin());
        $this->loginUid  = 0;
        if ( ($uinfo = posix_getpwnam($this->loginUser)) != false ) {
            $this->loginUid = $uinfo['uid'];
        }

        $this->json = $json;
    }

    public function actionStart($input)
    {
        $this->_do_request('start');
    }

    public function actionStop($input)
    {
        $this->_do_request('stop');
    }

    public function actionRestart($input)
    {
        $this->_do_request('restart');
    }

    /**
     * script monitor, check the running status of all the
     *  started script at specified interval and if the script is down start it
    */
    public function actionMonitor($input)
    {
        $interval = $input->getInt('interval', 10);    //in seconds
        $trackArr = array();
        foreach ( $this->json->cmd as $cmdObj ) {
            if ( isset($cmdObj->track) 
                && $cmdObj->track == false ) {
                continue;
            }

            if ( strpos($cmdObj->cmd, '/cli/') === false ) {
                echo "[Warning]: Nonstandard Cli_Controller script \"{$cmdObj->cmd}\"\n";
                continue;
            }

            //define the effective user id
            $user = isset($cmdObj->user) ? $cmdObj->user : 'root';
            $uid  = 0;
            if ( $user == '@login' ) {
                $user = $this->loginUser;
                $uid  = $this->loginUid;
            } else if ( ($uinfo = posix_getpwnam($user)) != FALSE ) {
                $uid  = $uinfo['uid'];
            }

            //define the final command
            $cmd  = str_replace('@action', 'start', $cmdObj->cmd);
            $pipe = isset($cmdObj->pipe) ? $cmdObj->pipe : '/dev/null';

            //define the process file
            $pFile = self::getPFile($cmdObj->cmd);

            $cmdObj->cmd    = $cmd;
            $cmdObj->uid    = $uid;
            $cmdObj->pid    = 0;
            $cmdObj->pipe   = $pipe;
            $cmdObj->user   = $user;
            $cmdObj->pFile  = $pFile;
            $cmdObj->fcount = 0;
            $trackArr[]     = $cmdObj;
            unset($user, $uid, $cmd, $pipe, $pFile);
        }

        if ( empty($trackArr) ) {
            echo "+-Empty track list\n";
            return;
        }

        echo "+-New monitor started\n";
        while (true) {
            if ( $this->process_state == CLI_PROC_EXIT ) {
                break;
            }

            foreach ( $trackArr as $key => $cmdObj ) {
                echo "+Test command \"{$cmdObj->cmd}\" ... ";
                if ( ! file_exists($cmdObj->pFile) ) {
                    echo "\n-Script normal terminated and try to start it ... \n";
                    echo "+Set effective user to {$cmdObj->user}#{$cmdObj->uid} ... ";
                    $set = posix_seteuid($cmdObj->uid);
                    if ( $set == false ) {
                        echo " --[Failed]\n";
                        echo "+Set effective user to {$this->loginUser}#{$this->loginUid} instead ... ";
                        echo (posix_seteuid($this->loginUid) ? " --[OK]\n" : " --[Failed]\n");
                    } else {
                        echo " --[Ok]\n";
                    }
                    echo "+Run command \"{$cmdObj->cmd}\"\n";
                    $cmdObj->fcount++;
                    shell_exec("{$cmdObj->cmd}:fcount={$cmdObj->fcount} > {$cmdObj->pipe} &");
                    continue;
                }

                $procInfo = json_decode(file_get_contents($cmdObj->pFile));
                if ( $procInfo == NULL ) {
                    echo "\n-Core dumped and will be removed\n";
                    unset($trackArr[$key], $procInfo);
                    continue;
                }

                $restart = true;
                $cmdObj->pid = $procInfo->pid;
                unset($procInfo);

                for ( $i = 0; $i < 3; $i++ ) {
                    if ( posix_kill($cmdObj->pid, 0) == true ) {
                        $restart = false;
                        break;
                    }
                }

                if ( $restart == false ) {
                    echo " --[Ok]\n";
                    continue;
                }

                echo "\n-Script abnormal terminated and try to start it ... \n";
                echo "+Set effective user to root#{$this->rootUid} ... ";
                echo (posix_seteuid($this->rootUid) ? " --[OK]\n" : " --[Failed]\n");
                @unlink($cmdObj->pFile);    //Abnormal exit

                echo "+Set effective user to {$cmdObj->user}#{$cmdObj->uid} ... ";
                $set = posix_seteuid($cmdObj->uid);
                if ( $set == false ) {
                    echo " --[Failed]\n";
                    echo "+Set effective user to {$this->loginUser}#{$this->loginUid} instead ... ";
                    echo (posix_seteuid($this->loginUid) ? " --[OK]\n" : " --[Failed]\n");
                } else {
                    echo " --[Ok]\n";
                }
                echo "+Run command \"{$cmdObj->cmd}\"\n";
                $cmdObj->fcount++;
                shell_exec("{$cmdObj->cmd}:fcount={$cmdObj->fcount} > {$cmdObj->pipe} &");
                unset($restart, $i, $set);
            }

            unset($key, $cmdObj);
            echo "+-Sleep for the next {$interval} seconds ... ";
            sleep($interval);
            echo " -[Done]\n";
        }

        //switch back to root
        echo "+-Set effective user back to root#{$this->rootUid} ... ";
        echo (posix_seteuid($this->rootUid)) ? " --[Ok]\n" : " --[Failed]\n";
        echo "+-script monitor terminated\n";
    }

    /**
     * process manager request handler
     *
     * @param   $action
    */
    private function _do_request($action)
    {
        //action define && execute
        foreach ( $this->json->cmd as $cmdObj ) {
            $cmd = str_replace('@action', $action, $cmdObj->cmd);

            //check and set the work user
            $uid  = 0;
            $user = isset($cmdObj->user) ? $cmdObj->user : 'root';
            if ( $user == '@login' ) {
                $user = $this->loginUser;
                $uid  = $this->loginUid;
            } else if ( ($uinfo = posix_getpwnam($user)) != FALSE ) {
                $uid  = $uinfo['uid'];
            }

            $pipe = isset($cmdObj->pipe) ? $cmdObj->pipe : '/dev/null';
            $cmd  = "{$cmd} > $pipe &";  //run in background

            echo "+Set effective user to {$user}#{$uid} ... ";
            $set  = posix_seteuid($uid);
            if ( $set == false ) {
                echo " --[Failed]\n";
                echo "+Set effective user to {$this->loginUser}#{$this->loginUid} instead ... ";
                echo (posix_seteuid($this->loginUid) ? " --[OK]\n" : " --[Failed]\n");
            } else {
                echo " --[Ok]\n";
            }

            echo "+Run command \"{$cmd}\"\n";
            shell_exec($cmd);
        }

        //@Note: we got to switch the user back to root
        echo "+-Set effective user back to root#{$this->rootUid} ... ";
        echo (posix_seteuid($this->rootUid)) ? " --[Ok]\n" : " --[Failed]\n";

        /*
         * @Note: added at 2016/06/24
         * stop the old monitor with the same instance first
         * and no matter there is an old one running or not.
         * and we got to wait the old terminated first.
        */
        echo "+-Stopping the monitor ... \n";
        $instand = md5(realpath($this->taskFile));
        $instand = substr($instand, 0, 4).substr($instand, 28);
        $cmd = "php server.php /cli/pm/monitor?instance={$instand}:action=stop:taskFile={$this->taskFile}";
        echo "+-Run command \"{$cmd}\"\n";
        shell_exec($cmd);
        echo "-[Done]\n";

        if ( $action == 'stop' || $this->monitor == false ) {
            return true;
        }

        //check and start the running script monitor
        echo "+-Starting the monitor ... \n";
        $logFile = ($this->logFile==FALSE) ? '/dev/null' : $this->logFile;
        $cmd = "php server.php /cli/pm/monitor?instance={$instand}:action=start:loginUser={$this->loginUser}:taskFile={$this->taskFile} > {$logFile} &";
        echo "+-Run command \"{$cmd}\"\n";
        shell_exec($cmd);
        echo "-[Done]\n";

        return true;
    }

    /**
     * parse and return the basic info
     *
     * @param   $cmd
     * @return  Array {
     *  path => 
     *  instance => 
     * }
    */
    private static function getPFile($cmd)
    {
        if ( ($qidx = strpos($cmd, '?')) !== false ) {
            $path  = substr($cmd, 0, $qidx);
            $query = substr($cmd, $qidx + 1);
        } else {
            $path  = $cmd;
            $query = NULL;
        }

        //extract the instance
        $instance = 'default';
        if ( $query != NULL 
            && preg_match('/instance=(\w+)/i', $query, $M) == 1 ) {
            $instance = $M[1];
        }

        //find the real path part
        $sIdx = strrpos($path, ' ');
        $path = substr($path, $sIdx + 1);

        //clear the cli prefix
        $sIdx = strpos($path, '/', 2);
        $path = substr($path, $sIdx + 1);

        unset($query, $sIdx);
        return SR_TMPPATH . "proc/{$path}/{$instance}.pid";
    }

}
?>
