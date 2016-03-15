<?php
/*
 * Gearman distributed synchronize/asynchronize executor
 * 
 * @author  chenxin<chenxin619315@gmail.com>
 */

defined('E_PRIORITY_LOW')     or define('E_PRIORITY_LOW',    0);
defined('E_PRIORITY_NORMAL')  or define('E_PRIORITY_NORMAL', 1);
defined('E_PRIORITY_HIGHT')   or define('E_PRIORITY_HIGHT',  2);

//-------------------------------------------------------

class GearmanExecutor
{
    /**
     * global gearman client object
     *
     * @access  private
    */
    private $GMC = NULL;

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
            $this->GMC->addOptions($conf['options'])
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
    public function execute($serv_path, $args, $asyn=false, $priority=E_PRIORITY_NORMAL)
    {
        $ret = false;
        $handler  = 'service_executor';
        $workload = serialize(array($serv_path, $args));

        switch ( $priority ) {
        case E_PRIORITY_LOW:
            //$fun_name = $asyn ? 'doLowBackground' : 'doLow';
            $ret = $asyn ? $this->doLowBackground($handler, $workload) : $this->doLow($handler, $workload);
            break;
        case E_PRIORITY_NORMAL:
            //$fun_name = $asyn ? 'doBackground' : 'doNormal';
            $ret = $asyn ? $this->doBackground($handler, $workload) : $this->doNormal($handler, $workload);
            break;
        case E_PRIORITY_HIGHT:
            //$fun_name = $asyn ? 'doHighBackground' : 'doHigh';
            $ret = $asyn ? $this->doHighBackground($handler, $args) : $this->doHigh($handler, $workload);
            break;
        default: return false;
        }

        //return $this->GMC->{$fun_name}('service_executor', $args);
        if ( $asyn ) {
            return $this->GMC->returnCode() == GEARMAN_SUCCESS ? true : false;
        }

        //check and decode the return arguments
        return unserialize($ret);
    }
}
?>
