<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian URI Manage Class
 * Offer interface to:
 *
 * 1. parse the request url
 * 2. make the style request url
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/Syrian
 */

 //---------------------------------------------------------
 
 //link style constants
 defined('URI_DIR_STYLE')    or define('URI_DIR_STYLE', 0);
 defined('URI_STD_STYLE')    or define('URI_STD_STYLE', 1);

 //---------------------------------------------------------
 
class Uri
{
    //request url
    public $url         = NULL;
    public $self        = NULL;
    
    //request module/page
    public $module      = NULL;
    public $page        = NULL;
    
    /**
     * request base part of the uri before the script file
     *      like    /syrian/skeleton/ of /syrian/skeleton/index.php
     *
     * @access  private
    */
    private $_base      = '/';
    
    /**
     * require rounter part of the url, eg: article/list
     *      of syrian/skeleton/index.php/article/list.html
     *
     * @access  private
    */
    private $_request   = NULL;
    
    //link style (@see Uri#style constants)
    private $_style     = NULL;
    
    //request script file extension
    private $_ext       = NULL;
    
    //use url rewrite, hide the 'index.php' in request url
    private $_rewrite   = false;
    private $_parts     = NULL;
    
    /**
     * construct method to initialize the class
     *
     * @param   $_rewrite   start the url rewrite?
     * @param   $_style get http request link style
    */
    public function __construct( $_rewrite = false, $_style = URI_STD_STYLE )
    {
        //copy
        $this->url      = $_SERVER['REQUEST_URI'];
        $this->self     = $_SERVER['PHP_SELF'];
        
        //normalized the url and make sure it start with /
        if ( $this->url[0] != '/' )     $this->url = '/' . $this->url;
        if ( $this->self[0] != '/' )    $this->self = '/' . $this->self;
        
        /*
         * Analysis and initialize the base
        */
        if ( ($pos = stripos($this->self, '.php')) !== FALSE )
        {
            while ( $this->self[$pos] != '/' ) $pos--;
             //get the base part, include the '/' mark at $i
            if ( $pos > 0 ) $this->_base = substr($this->self, 0, $pos + 1);
        }
        
        $this->_rewrite = $_rewrite;
        $this->_style = $_style;
    }
    
    /**
     * parse the current request url to find the module
     *  page arguments, also handler the arguments as need
     *
     * @return  bool
    */
    public function parseUrl()
    {
        $_spos  = 0;     //start position to determine the request
        $_epos  = 0;     //end position to determine the request
        $pos    = 0;     //temp variable
        $_len   = strlen($this->url);
        
        if ( ($pos = stripos($this->url, '.php')) !== FALSE )
            $_spos = $pos + 4;
        
        /*
         * move forward the start position, cause:
         * 1. exclude the '/' punctuation at 0 when match no '.php'
         * 2. skip the '/' punctuation after '.php' if available
        */
        if ( $_spos < $_len ) $_spos++;
        
        //check and find the end index
        $_args = stripos($this->url, '?', $_spos);
        $_extp = stripos($this->url, '.', $_spos);
        
        //determine the end index
        if ( $_args !== FALSE && $_extp !== FALSE )
            $_epos = min($_args, $_extp);
        else
            $_epos = max($_args, $_extp);
        
        /*
         * mark the final end position
         *  And clear the last '/' punctuation if it is
        */
        if ( $_epos == FALSE ) $_epos = $_len;
        if ( $this->url[$_len - 1] == '/' ) $_epos--;
        $this->_request = substr($this->url, $_spos, $_epos - $_spos);
        if ( $this->_request == '' ) return false;
        
        /*
         * split the request and parse to get the module and page info
         *      also, initialize the _parts globals variable here
        */
        $_ret = explode('/', $this->_request);
        $this->_parts = &$_ret;
            
        //make the mdoule and the page
        $this->module = $_ret[0];
        if ( isset($_ret[1]) )  $this->page = $_ret[1];
        
        return true;
    }
    
    /**
     * Exit the current process and tell the client
     *      to goto the speicifled url by http location header
     *
     * @param   $_url   request url, 'module/page'
     * @param   $_args  request arguments
     * @param   $_ext   url file extension
    */
    public function redirect( $_url, $_args = NULL )
    {
        $_url = $this->makeStyleUrl($_url, $_args);
        @header('Location: ' . $_url);
        exit();
    }
    
    /**
     * Make a valid http get requst arguments with the specifiled
     *      request link style
     *
     * @param   $_args quote
     * @return  String
     * @access  private
    */
    private function makeStyleArgs( &$_args )
    {
        //dir style arguments, demo: /nid/tid/pageno
        if ( $this->_style == URI_DIR_STYLE )
            return ('/'.implode('/', $_args));
        
        if ( is_string($_args) ) return ('?' . $_args);
        
        /*
         * Consider the args as an key=>value array
         *  make a valid http get request arguments string
         * like 'key=val&key2=val2'
        */
        $_str = ''; $item = NULL;
        foreach ( $_args as $_key => $_val )
        {
            $item = $_key . '=' . $_val;
            $_str .= ($_str == '') ? $item : '&' . $item;
        }
        
        return ('?' . $_str);
    }
    
    /**
     * Make a valid request url with the specifiled request arguments
     *
     * @param   $_url
     * @param   $_args  key=>val arguments array
     * @param   $_ext   url file extension
     * @return  String  a valid request url
    */
    public function makeStyleUrl($_url, $_args = NULL)
    {
        $_uri  = $this->_base;
        $_uri .= ($this->_rewrite ? '' : 'index.php/') . $_url;
        if ( $this->_ext != NULL ) $_uri .= $this->_ext;
        
        //check and append the arguments as needed
        if ( $_args != NULL ) $_uri .= $this->makeStyleArgs($_args);
        
        return $_uri;
    }
    
    /**
     * set the file extension of the request url
     *
     * @param   $_ext   should start with '.'
    */
    public function setFileExt( $_ext )
    {
        $this->_ext = &$_ext;
    }
    
    /**
     * parse the directory style http get arguments
     *      to the global $_GET array with a specifial template
     *
     * @param   $_temp style like nid/tid/pageno
     * @return  Integer - number of successfully parsed arguments 
    */
    public function parseArgsGet( $_temp )
    {
        //check and make sure the uri style is URI_DIR_STYLE
        if ( $this->_style != URI_DIR_STYLE ) return 0;
        
        $_counter = 2;
        $_length = count($this->_parts);
        
        $_keys = explode('/', $_temp);
        foreach ( $_keys as $_key )
        {
            //mapping NULL with the $_key in $_GET
            if ( $_counter >= $_length )
            {
                $_GET[$_key] = NULL;
                continue;
            }
            
            //mapping $_counter's value of _parts with key $_key
            //  in global $_GET array
            $_GET[$_key] = $this->_parts[$_counter];
            $_counter++;
        }
    }
}
?>