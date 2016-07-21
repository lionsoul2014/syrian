<?php
/**
 * CronTestController Controller manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

import('core.Cli_Controller', false);

 //------------------------------------------------

class TestController extends Cli_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __before($uri, $input, $output)
    {
        parent::__before($uri, $input, $output);
    }
    
    public function _base($input, $output)
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
