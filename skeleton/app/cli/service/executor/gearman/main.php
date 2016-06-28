<?php
/**
 * Service gearman executor server
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

//------------------------------------

defined('SR_SERVICE_WORKER') or define('SR_SERVICE_WORKER', true);

class GearmanController extends CliController
{
    private $debug  = false;
    private $maxmem = NULL;

    /**
     * the local service executor
     *
     * @access  private
    */
    private $localExecutor = NULL;

    /**
     * construct method
    */
    public function __construct()
    {
        parent::__construct();
    } 

    /**
     * controller entrace method you could use the default one
     *      by just invoke parent::run() or write you own implementation
     *
     * @see Controller#run()
    */
    public function run()
    {
        parent::run();

        $this->debug  = $this->input->getBoolean('debug', false);
        $this->maxmem = $this->input->getInt('maxmem', 0);

        Loader::import('LocalExecutor', 'service');
        $this->localExecutor = new LocalExecutor(NULL);

        if ( strncmp($this->uri->page, 'worker', 7) == 0 )  $this->_worker();
    }

    /**
     * gearman service executor worker
    */
    private function _worker()
    {
        $sharding = $this->input->get('sharding');
        if ( $sharding == false ) {
            exit("Error: Missing sharding arguments\n");
        }

        $conf = Loader::config('executor', NULL, false, $sharding);
        if ( $conf == false ) {
            exit("Error: Invalid sharding arguments {$sharding} \n");
        }

        if ( $this->maxmem > 0 ) {
            echo "set the max memory to {$this->maxmem}MB ... ";
            if ( ini_set('memory_limit', "{$this->maxmem}M") === false ) {
                echo " --[Failed]\n";
            } else {
                echo " --[Ok]\n";
            }
        }


        $worker = new GearmanWorker();

        if ( isset($conf['servers']) ) {
            foreach ( $conf['servers'] as $server ) {
                $worker->addServer($server[0], $server[1]);
            }
        } else {
            $worker->addServer();    //add the default server
        }

        //check and set the options
        if ( isset($conf['options']) && $conf['options'] != NULL ) {
            $worker->addOptions($conf['options']);
        }

        unset($sharding, $conf);
        $worker->addFunction('service_executor', array($this, 'execute'));
        echo "+-[Info]: Worker Started at ", date('Y-m-d H:i:s'), "\n";
        while ($worker->work()) {
            sleep(0);   //so, the singal could work
            //check and aplly the process state change
            if ( $this->process_state == CLI_PROC_EXIT ) {
                break;
            }
        }
    }

    /**
     * do the final service execute
     *
     * @param   $job
     * @return  Mixed whatever the service return
    */
    public function execute($job)
    {
        //check and unserialize the workload
        $args = unserialize($job->workload());

        //check and print the debug info
        if ( $this->debug ) {
            $this->printDebugInfo($args);
        }
            
        try {
            $ret = serialize($this->localExecutor->execute($args[0], $args[1]));
        } catch (Exception $e) {
            //@TODO: do error log here
        }

        unset($args);

        return $ret;
    }

    /**
     * print the service debug info
     *
     * @param   $args
    */
    public function printDebugInfo($args)
    {
        echo "Task: \n";
        echo "service path: {$args[0]}\n";
        echo "service args: {\n";
        var_dump($args[1]);
        echo "}\n";
    }

}
?>
