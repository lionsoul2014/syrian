<?php
/**
 * CronTestController Controller manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

class TestController extends Cli_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function actionBase($input)
    {
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
