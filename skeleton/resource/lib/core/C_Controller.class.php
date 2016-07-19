<?php
/**
 * Common Controller supper class for common application module
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-----------------------------------------------------
 
class C_Controller extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Rewrite the run method
     *      to add some basic initialize
    */
    public function run()
    {
        parent::run();
    }

}
?>
