<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
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
 
 //link style constants
 defined('URI_DIR_STYLE')    or define('URI_DIR_STYLE', 0);
 defined('URI_CON_STYLE')    or define('URI_CON_STYLE', 1);

 //---------------------------------------------------------
 
class Uri
{
    /**
     * url style connector
     *
     * @access private
    */
    private static $_connector = array('/', '-');
    
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
     * require script file name, It will always be NULL
     *      if we start the url rewrite and access the page
     *  like syrian/skeleton/article/list/:navid/:tid/:pageno
     *
     * @access  private
    */
    private $_file      = NULL;
    
    /**
     * require rounter part of the url, eg:
     *  article/list or article-list of
     *      syrian/skeleton/index.php/article/list.html
     *
     * @access  private
    */
    private $_request   = NULL;
    
    //link style
    private $_style     = NULL;
    
    /**
     * construct method to initialize the class
    */
    public function __construct( $_style = URI_DIR_STYLE )
    {
        $this->url      = $_SERVER['REQUEST_URI'];
        $this->self     = $_SERVER['PHP_SELF'];
        
        //normalized the url and make sure it start with /
        if ( $this->url[0] != '/' )
            $this->url = '/' . $this->url;
        
        //set the link style
        $this->_style = $_style;
    }
    
    /**
     * parse the current request url and
     *  store the path info to the $request array
    */
    public function parse_url()
    {
        $_spos  = 0;     //start position
        $_epos  = 0;     //end position
        $pos    = 0;     //position temp
        
        //check and determine the start index
        if ( ($pos = stripos($this->url, '.php')) !== FALSE )
        {
            $i = $pos - 1;
            while ( $this->url[$i] != '/' ) $i--;
            
            /*
             * get the filename and the base part
             *  start position is $i + 1 and the length
             * is $pos - $i - 1 + 4 = $pos - $i + 3
            */
            $this->_file = substr($this->url, $i + 1, $pos - $i + 3);
            if ( $i >= 0 ) $this->_base = substr($this->url, 0, $i + 1);
            
            $_spos = $pos + 4;
        }
        
        if ( $_spos < strlen( $this->url ) ) $_spos++;
        
        //check and find the end index
        $_args = stripos($this->url, '?', $_spos);
        $_extp = stripos($this->url, '.', $_spos);
        
        //determine the end index
        if ( $_args !== FALSE && $_extp !== FALSE )
            $_epos = min($_args, $_extp);
        else
            $_epos = max($_args, $_extp);
        
        //mark the final end position
        if ( $_epos == FALSE ) $_epos = strlen($this->url);
        $this->_request = substr($this->url, $_spos, $_epos - $_spos);
        
        if ( $this->_request == '' ) return false;
        
        //parse to get the module and page info
        $_ret = explode(self::$_connector[$this->_style], $this->_request);
        
        //make the mdoule and the page
        $this->module = $_ret[0];
        if ( isset($_ret[1]) )  $this->page = $_ret[1];
    }
    
    /**
     * Exit the current process and tell the client
     *      to goto the speicifled url by http location header
     *
     * @param   $_module    - request action
     * @param   $_page
     * @param   $_args  request arguments
     * @param   $_ext   url file extension
    */
    public function redirect( $_module, $_page, $_args = NULL, $_ext = '.html' )
    {
        $_url = $this->makeStyleUrl($_module, $_page, $_args, $_ext);
        @header('Location: ' . $_url);
        exit();
    }
    
    /**
     * Make a valid request url with the specifiled request arguments
     *
     * @param   $_module
     * @param   $_page
     * @param   $_args  key=>val arguments array
     * @param   $_ext   url file extension
     * @return  String  a valid request url
    */
    public function makeStyleUrl($_module, $_page, $_args = NULL, $_ext = '.html')
    {
        $_url  = $this->_base;
        if ( $this->_file != NULL ) $_url .= $this->_file . '/';
        $_url .= $_module . self::$_connector[$this->_style];
        $_url .= $_page . $_ext;
        
        //check and append the arguments as needed
        if ( $_args != NULL )
        {
            if ( is_string($_args) ) $_url .= '?' . $_args;
            else
            {
                //make a valid the http request arguments
                $_str = ''; $item = NULL;
                foreach ( $_args as $_key => $_val )
                {
                    $item = $_key . '=' . $_val;
                    $_str .= ($_str == '') ? $item : '&' . $item;
                }
                
                //append the arguments
                $_url .= '?' . $_str;
            }
        }
        
        return $_url;
    }
}
?>