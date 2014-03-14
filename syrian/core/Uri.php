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
    
    //request url with arguments
    public $url         = NULL;
    
    //request arguments
    public $args        = NULL;
    
    //request path
    public $request     = NULL;
    
    //request module
    public $module      = NULL;
    
    //request page
    public $page        = NULL;
    
    //link style
    private $_style     = NULL;
    
    /**
     * construct method to initialize the class
    */
    public function __construct( $_style = URI_DIR_STYLE )
    {
        $this->url      = $_SERVER['REQUEST_URI'];
        $this->args     = $_SERVER['QUERY_STRING'];
        
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
            $_spos = $pos + 4;
        
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
        
        //echo 'end: ', $_spos, ', ' . $_epos, '<br />';
        $this->request = substr($this->url, $_spos, $_epos - $_spos);
        
        if ( $this->request == '' ) return false;
        
        //parse to get the module and page info
        $_ret = explode(self::$_connector[$this->_style], $this->request);
        
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
        $_url  = $_module . self::$_connector[$this->_style];
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