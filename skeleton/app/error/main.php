<?php
/**
 * ErrorController
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class ErrorController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function run()
    {
        $_method = 'err_' . $this->uri->page;
        
        //invoke the specifiled method
        if ( method_exists($this, $_method) )
        {
            $this->{$_method}();
        }
        else
        {
            $this->error();
        }
    }
    
    public function error()
    {
        
    }
    
    public function err_404()
    {
        echo '404 that\' an error.';
    }
}
?>
