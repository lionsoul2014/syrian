<?php
/*
 * Gearman distributed synchronize/asynchronize executor
 * 
 * @author  chenxin<chenxin619315@gmail.com>
 */

defined('E_PRIORITY_LOW')     or define('E_PRIORITY_LOW',    0);
defined('E_PRIORITY_NORMAL')  or define('E_PRIORITY_NORMAL', 1);
defined('E_PRIORITY_HIGHT')   or define('E_PRIORITY_HIGHT',  2);

//--------------------------------------------------

class GearmanExecutor
{
    /**
     * global gearman client object
     *
     * @access  private
    */
    private $GMC = NULL;

    /**
     * use the local executor to instead if
     * the target gearman server is down
     *
     * @access  private
    */
    private $local_ensure = false;

    /**
     * create and initialize the gearman executor
     *
     * @param   $conf
    */
    public function __construct($conf)
    {
        $this->GMC = new GearmanClient();
        
        if ( isset($conf['servers']) ) {
            foreach ( $conf['servers'] as $server ) {
                $this->GMC->addServer($server[0], $server[1]);
            }
        } else {
            $this->GMC->addServer();    //add the default server
        }

        //check and set the options
        if ( isset($conf['options']) && $conf['options'] != NULL ) {
            $this->GMC->addOptions($conf['options']);
        }

        if ( isset($conf['local_ensure']) ) {
            $this->local_ensure = $conf['local_ensure'];
        }
    }

    /**
     * execute the specifield service through normal function invoke
     *
     * @param   $serv_path
     * @param   $args Array
     * @param   $asyn
     * @param   $priority
     * @return  Mixed
    */
    public function execute($serv_path, $args, $asyn=true, $priority=E_PRIORITY_NORMAL)
    {
        $ret = false;
        $handler  = 'service_executor';
        $workload = serialize(array($serv_path, $args));
        $gmc = $this->GMC;

        switch ( $priority ) {
        case E_PRIORITY_LOW:
            $ret = $asyn ? $gmc->doLowBackground($handler, $workload) : $gmc->doLow($handler, $workload);
            break;
        case E_PRIORITY_NORMAL:
            $ret = $asyn ? $gmc->doBackground($handler, $workload) : $gmc->doNormal($handler, $workload);
            break;
        case E_PRIORITY_HIGHT:
            $ret = $asyn ? $gmc->doHighBackground($handler, $workload) : $gmc->doHigh($handler, $workload);
            break;
        default: return false;
        }

        if ( $gmc->returnCode() == GEARMAN_SUCCESS ) {
            return $asyn ? true : unserialize($ret);
        }

        /*
         * service execute failed, no matter what
         * if the local ensurance is opened
         * we should execute the service locally.
        */
        if ( $this->local_ensure == true ) {
            Loader::import('LocalExecutor', 'service');
            $executor = new LocalExecutor(NULL);
            return $executor->execute($serv_path, $args, $asyn, $priority);
        }

        //echo 'GEARMAN_SUCCESS: ', GEARMAN_SUCCESS, "\n";
        //echo 'GEARMAN_PAUSE: ', GEARMAN_PAUSE, "\n";
        //echo 'GEARMAN_IO_WAIT: ', GEARMAN_IO_WAIT, "\n";
        //echo 'GEARMAN_WORK_STATUS: ', GEARMAN_WORK_STATUS, "\n";
        //echo 'GEARMAN_WORK_DATA: ', GEARMAN_WORK_DATA, "\n";
        //echo 'GEARMAN_WORK_EXCEPTION: ', GEARMAN_WORK_EXCEPTION, "\n";
        //echo 'GEARMAN_WORK_WARNING: ', GEARMAN_WORK_WARNING, "\n";
        //echo 'GEARMAN_WORK_FAIL: ', GEARMAN_WORK_FAIL, "\n";
        //echo 'GEARMAN_SHUTDOWN: ', GEARMAN_SHUTDOWN, "\n";
        //echo 'GEARMAN_NO_SERVERS: ', GEARMAN_NO_SERVERS, "\n";
        //echo 'GEARMAN_COULD_NOT_CONNECT: ', GEARMAN_COULD_NOT_CONNECT, "\n";
        //echo 'GEARMAN_COMMAND_CAN_DO_TIMEOUT: ', GEARMAN_COMMAND_CAN_DO_TIMEOUT, "\n";
        //switch ( $retCode ) {
        //case GEARMAN_SUCCESS:
        //    echo 'GEARMAN_SUCCESS';
        //    break;
        //case GEARMAN_PAUSE:
        //    echo 'GEARMAN_PAUSE';
        //    break;
        //case GEARMAN_IO_WAIT:
        //    echo 'GEARMAN_IO_WAIT';
        //    break;
        //case GEARMAN_WORK_STATUS:
        //    echo 'GEARMAN_WORK_STATUS';
        //    break;
        //case GEARMAN_WORK_DATA:
        //    echo 'GEARMAN_WORK_DATA';
        //    break;
        //case GEARMAN_WORK_EXCEPTION:
        //    echo 'GEARMAN_WORK_EXCEPTION';
        //    break;
        //case GEARMAN_WORK_WARNING:
        //    echo 'GEARMAN_WORK_WARNING';
        //    break;
        //case GEARMAN_WORK_FAIL:
        //    echo 'GEARMAN_WORK_FAIL';
        //    break;
        //}

        return false;
    }
}
?>
