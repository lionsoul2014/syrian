<?php if ( ! defined('APPPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian URI Manage Class
 * Offer interface to:
 *
 * 1. parse the request url
 * 2. make the request url
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/Syrian
 */

 //---------------------------------------------------------
 
class Uri
{
    //current request url with arguments
    public $request_url     = NULL;
    
    //current request url without arguments
    public $request_path    = NULL;
    
    //current request arguments
    public $request_args    = NULL;
    
    //request module
    private $module         = NULL;
    
    //request page
    private $page           = NULL;
    
    /**
     * construct method to initialize the class
    */
    public function __construct()
    {
        $this->request_url      = &$_SERVER['REQUEST_URI'];
        $this->request_script   = &$_SERVER['SCRIPT_NAME'];
        $this->request_args     = &$_SERVER['QUERY_STRING'];
    }
    
    /**
     * parse the current request url and
     *  store the path info to the $request array
    */
    public function parse_url()
    {
        
    }
}
?>