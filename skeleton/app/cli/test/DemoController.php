<?php
/**
 * CronTestController Controller manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

class DemoController extends Cli_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Input $input
     */
    public function index($input)
    {
        tprintOk("Hello, World");
    }

}
