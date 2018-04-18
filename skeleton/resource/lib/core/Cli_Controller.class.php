<?php
/**
 * Common Controller base class for cli logic
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

//-----------------------------------------------------

import('Util');

defined('CLI_PROC_EXIT')    or define('CLI_PROC_EXIT',   -1);
defined('CLI_PROC_RUNNING') or define('CLI_PROC_RUNNING', 1);
defined('CLI_PROC_PAUSE')   or define('CLI_PROC_PAUSE',   2);

class Cli_Controller extends Controller
{
    /**
     * avalable action mapping
    */
    private static $action_mapping = array(
        'start'     => true,
        'stop'      => true,
        'restart'   => true,
        'stat'      => true,
        'pause'     => true,
        'resume'    => true
    );

    /**
     * user defined singal and handler
    */
    private $signal_mapping = array(
        SIGHUP  => NULL,    //hangup 
        SIGINT  => NULL,    //Interrupt from keyboard
        SIGQUIT => NULL,    //Quit from keyboard
        SIGTERM => NULL,    //Termination signal
        //SIGUSR1 => NULL,    //user define signal 1
        //SIGUSR2 => NULL     //user define signal 2,

        //more user define mapping
    );

    /**
     * the instance name of the process
    */
    private $instance = NULL;

    /**
     * the action of the process
    */
    private $action   = NULL;

    /**
     * the pid path and the pid file path
    */
    protected $pidPath  = NULL;
    protected $pidFile  = NULL;

    /**
     * clean the process info file?
    */
    private $cleanProcInfo = false;

    /**
     * process running state
    */
    protected $process_state = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function __before($input, $output, $uri)
    {
        //cli limitation
        if ( SR_CLI_MODE == false ) {
            exit("Error: Access only for cli sapi.\n");
        }

        if ( ! extension_loaded('posix') ) {
            exit("Error: Posix extension need to run the script.\n");
        }

        if ( ! extension_loaded('pcntl') ) {
            exit("Error: Pcntl extension need to run the script.\n");
        }

        $this->instance = $input->get('instance', NULL, 'default');
        $this->action   = $input->get('action', NULL, 'start');
        if ( ! isset(self::$action_mapping[$this->action]) ) {
            exit("Error: Unknow action {$this->action}\n");
        }

        $ruri = $uri->path;
        $sIdx = strpos($ruri, '/', 2);
        if ( $sIdx === false ) {
            exit("Error: Internal error.\n");
        }

        //define the pid file
        $this->pidPath = substr($ruri, $sIdx+1)."/{$this->instance}.pid";
        $this->pidFile = SR_TMPPATH . "proc/{$this->pidPath}";
        Util::makePath(dirname($this->pidFile));

        /*
         * action dispatch logic:
         * intercept the stop and restart action
         * for both of them got to stop the program first
        */
        $continue = false;
        switch ( $this->action ) {
        case 'stat':
            $this->printInstanceProcInfo();
            break;
        case 'pause' :
        case 'resume':
            $procInfo = $this->getInstanceProcInfo();
            if ( $procInfo == NULL ) {
                $this->procStatError(true);
            }

            $signo = $this->action == 'pause' ? SIGSTOP : SIGCONT;
            echo "+-Try to send {$this->action} signal to process with pid={$procInfo->pid} ... ";
            if ( posix_kill($procInfo->pid, $signo) == false ) {
                echo " --[Failed]\n";
            } else {
                echo " --[Ok]\n";
            }
            break;
        case 'stop':
            $procInfo = $this->getInstanceProcInfo();
            if ($procInfo == NULL) {
                $this->procStatError(true);
            }

            //try to stop the coming instance
            self::__stopProcessMacro($procInfo);

            /*
             * clean the process info
             * cuz the exited process will clean the proc info itself
             * Do not bother to clean the process info here
            */
            //echo "+-Try to clean the process info ... ";
            //if ( $this->cleanInstanceProcInfo() == false ) {
            //    echo "\n|--Failed, remove file {$this->pidFile} yourself please!\n";
            //}
            //echo " --[Ok]";
            break;
        case 'restart':
            $procInfo = $this->getInstanceProcInfo();
            //check and try to stop the process info
            if ( $procInfo != NULL ) {
                self::__stopProcessMacro($procInfo);
            }

            $continue = true;
            break;
        case 'start':
            $continue = true;
            break;
        }

        if ( $continue == false ) {
            exit(0);
        }

        /*
         * from here the start logic will executed
         * write the process info and It will auto clean in the __destruct
         *
         * Also, the start state of the current instance will be checked
         * at writeInstanceProcInfo, it will return false it so.
        */
        //echo "+-Try to write the proc info ... ";
        if ( $this->writeInstanceProcInfo() == false ) {
            $errmsg = <<<EOF
Error: Unable to write the process info:
Is instance "{$this->instance}" started already ?
Or, make sure you got the permission to do this!\n
EOF;
            exit($errmsg);
        }


        //set the signal handler and the ticks
        if ( $this->registerSignalHandler() == false ) {
            exit("Error: Fail to register the signal handler.\n");
        }

        //mark the process info to auto clean in __desctruct
        $this->cleanProcInfo = true;
        $this->process_state = CLI_PROC_RUNNING;

        //set the ticks as sginal callback mechanism
        declare(ticks=1);
        
        //do whatever you want now ...
    }
    
    /**
     * default signal handler functions
     *
     * @param   $signo
    */
    public function signal_handler($signo)
    {
        switch ( $signo ) {
        case SIGHUP :
        case SIGINT :
        case SIGQUIT:
        case SIGTERM:
            /*
             * Any registed signal directly lead to stop the process
             * @date: 2016-01-21
            */
            $this->process_state = CLI_PROC_EXIT;
            break;
        //case SIGUSR1:
        //case SIGUSR2:
        //    break;
        //case SIGSTOP:
        //    $this->process_state = CLI_PROC_PAUSE;
        //    break;
        //case SIGCONT:
        //    $this->process_state = CLI_PROC_RUNNING;
        //    break;
        default:
            break;
        }
    }

    /**
     * singal installer
     *
     * @return  boolean
    */
    protected function registerSignalHandler()
    {
        foreach ( $this->signal_mapping as $signo => $sighdl ) {
            $sigHandler = ($sighdl == NULL) ? array($this, 'signal_handler') : $sighdl;
            if ( pcntl_signal($signo, $sigHandler) == false ) {
                return false;
            }
        }

        return true;
    }

    /**
     * set the signal handler
     *
     * @param   $signo
     * @param   $handler
     * @param   $override
     * @return  boolean
    */
    protected function setSignalHandler($signo, $handler, $override=false)
    {
        if ( $signo == SIGINT ) return false;
        if ( $override == false 
            && isset($this->signal_mapping[$signo]) ) {
            return false;
        }

        $this->signal_mapping[$signo] = $handler;
        return true;
    }

    /**
     * dispatch the signal and check the process running status
     * check and exit the process safely
     *
     * @param   $msg Exit message
    */
    protected function dispatchSignal()
    {
        pcntl_signal_dispatch();
        if ( $this->process_state == CLI_PROC_EXIT ) {
            exit(0);
        }
    }

    /**
     * check and stop the running process macro
     *
     * @param   $procInfo
    */
    private static function __stopProcessMacro($procInfo)
    {
        //try to stop the coming instance
        //1. send the process a signal named SIGINT
        echo "+-Try to send the SIGINT sinal to the process ... ";
        if (posix_kill($procInfo->pid, SIGINT) == false) {
            $errmsg = <<<EOF
\nError: Unable to send the SIGINT signal to process with pid={$procInfo->pid}
Is the process being killed ? \n
EOF;
            exit($errmsg);
        }
        echo " --[Ok]\n";

        //2. monitor the process status
        echo "+-Wait for the process to exit .. ";
        while (posix_kill($procInfo->pid, 0) == true) {
            sleep(1);
            echo '.';
        }
        echo " --[Ok]\n";
    }

    /**
     * get the process info
     *
     * @return  boolean
    */
    protected function getInstanceProcInfo()
    {
        if ( ! file_exists($this->pidFile) ) {
            return NULL;
        }

        $str  = file_get_contents($this->pidFile);
        if ( $str == false ) {
            return NULL;
        }

        return json_decode($str);
    }

    /**
     * write the process info
     *
     * @return  boolean
    */
    protected function writeInstanceProcInfo()
    {
        if ( file_exists($this->pidFile) ) {
            return false;
        }

        $data = array(
            'instance'      => $this->instance,
            'pid'           => posix_getpid(),
            'start_time'    => time()
        );

        $str = json_encode($data);
        $ret = file_put_contents($this->pidFile, $str);
        return ($ret == strlen($str));
    }

    /**
     * clear the process info
     *
     * @return  boolean
    */
    protected function cleanInstanceProcInfo()
    {
        if ( file_exists($this->pidFile) ) {
            return unlink($this->pidFile);
        }

        return false;
    }

    /**
     * print the process info
     *
     * @return void
    */
    protected function printInstanceProcInfo()
    {
        $procInfo = $this->getInstanceProcInfo();
        if ( $procInfo == NULL ) {
            $this->procStatError(true);
        }

        $output   = array();
        $output[] = "+----------process stat start--------+";
        $output[] = "pid        : {$procInfo->pid}";
        //$output[] = "state      : {$state}";
        $output[] = "instance   : {$procInfo->instance}";
        $output[] = "start time : {$procInfo->start_time}";
        $output[] = "+----------process stat end----------+";

        echo implode("\n", $output), "\n";
    }

    /**
     * print the process info stat error
     *
     * @param   $exit
    */
    private function procStatError($exit=false)
    {
        $errmsg = <<<EOF
Error: Can not stat the process info 
Is the instance "{$this->instance}" started yet?\n
EOF;
        echo $errmsg;

        if ($exit) {
            exit(0);
        }
    }
    
    /**
     * destruct method
     * auto process info clean
    */
    public function __destruct()
    {
        if ( $this->cleanProcInfo ) {
            $this->cleanInstanceProcInfo();
        }
    }

}
?>
