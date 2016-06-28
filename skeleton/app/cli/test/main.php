<?php
/**
 * CronTestController Controller manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

 //------------------------------------------------

class TestController extends CliController
{
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

        if ( strncmp($this->uri->page, 'base', 4) == 0 )   $this->_base();
    }

    public function _base()
    {
        //echo "Yat, the cpu has got me\n";
        $counter = 1;
        while ( true ) {
            echo "long running task is going to being execute for the {$counter} time ... \n";
            $counter++;
            sleep(2);
            echo "|----[complete]\n";

            //check and handler the process state change
            if ( $this->process_state == CLI_PROC_EXIT ) {
                break;
            }
        }

        echo "|--Process exited\n";
    }

}
?>
