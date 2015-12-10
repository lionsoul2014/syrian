<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Input Manager Class
 * Offer interface to:
 * 
 * 1. Quick lanch the input source
 * 2. Data type check and convertor
 *
 * @author    chenxin <chenxin619315@gmail.com>
 * @link    http://www.lionsoul.org/syrian
 */

 //--------------------------------------------------------------
//normal data type
defined('OP_NULL')        or define('OP_NULL',         1 <<  0);
defined('OP_LATIN')        or define('OP_LATIN',         1 <<  1);
defined('OP_URL')        or define('OP_URL',         1 <<  2);
defined('OP_EMAIL')        or define('OP_EMAIL',         1 <<  3);
defined('OP_QQ')        or define('OP_QQ',            1 <<  4);
defined('OP_DATE')        or define('OP_DATE',         1 <<  5);
defined('OP_NUMERIC')    or define('OP_NUMERIC',     1 <<  6);
defined('OP_STRING')    or define('OP_STRING',        1 <<  7);
defined('OP_ZIP')        or define('OP_ZIP',         1 <<  8);
defined('OP_CELLPHONE') or define('OP_CELLPHONE',     1 <<  9);
defined('OP_TEL')        or define('OP_TEL',         1 << 10);
defined('OP_IDENTIRY')  or define('OP_IDENTIRY',     1 << 11);

//santilize type
defined('OP_SANITIZE_TRIM')        or define('OP_SANITIZE_TRIM',         1 << 0);
defined('OP_SANITIZE_SCRIPT')    or define('OP_SANITIZE_SCRIPT',     1 << 1);
defined('OP_SANITIZE_HTML')        or define('OP_SANITIZE_HTML',         1 << 2);
defined('OP_MAGIC_QUOTES')        or define('OP_MAGIC_QUOTES',         1 << 3);
defined('OP_SANITIZE_INT')        or define('OP_SANITIZE_INT',         1 << 4);

if ( ! function_exists('OP_LIMIT') )
{
    function OP_LIMIT( $s, $e = -1 ) {return ( $e == -1 ? array(0, $s) : array(0, $s, $e) );}
}

if ( ! function_exists('OP_SIZE') )
{
    function OP_SIZE( $s, $e = -1 ) {return ($e == -1 ? array(1, $s) : array(1, $s, $e));}
}

class Input
{
    private static $_loaded = false;
    
    public function __construct()
    {
       //Do nothing here
    }
    
    /**
     * check and load the Filter class if it is not load
     *
     * @see    lib.util.filter.Filter
    */
    private static function checkAndLoadFilter()
    {
        //check the load status of Filter class
        if ( self::$_loaded == false )
        {
            //echo 'Filter class loaded';
            Loader::import('Filter');
            self::$_loaded = true;
        }
    }
   
   /**
    * fetch item from $_GET data source
    *
    * @param    $_key
    * @param    $_model
    * @param    $_default
    * @param    $_errno
    * @return    Mixed(Array, String, Bool)
   */
    public function get( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset( $_GET[$_key] ) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_GET, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_GET[$_key];
    }
    
    /**
     * Fetch an integer form $_GET global array
     *
     * @param    $_key
     * @param    $_default
     * @param    $allow_nagative
     * @return    Mixed(Integer or false)
    */
    public function getInt( $_key, $_default=false, $allow_nagative=false )
    {
        if ( ! isset( $_GET[$_key] ) ) return $_default;
        
        $v    = intval($_GET[$_key]);
        if ( $v < 0 && $allow_nagative == false )
        {
            return false;
        }

        return $v;
    }
    
    /**
     * fetch item from $_GET with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function getModel( $_model, &$_errno )
    {
        //check the load status of Filter class
        self::checkAndLoadFilter();
            
        return Filter::loadFromModel($_GET, $_model, $_errno);
    }
    
    //----------------------------------------------------------
    
    /**
     * fetch item from $_POST data source
     *
     * @param    $_key
     * @param    $_model
     * @param    $_default
     * @param    $_errno
     * @return    Mixed
    */
    public function post( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_POST[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_POST, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_POST[$_key];
    }
    
    /**
     * Fetch an integer form $_POST global array
     *
     * @param    $_POST
     * @param    $_default
     * @param    $allow_nagative
     * @return    Mixed(Integer or false)
    */
    public function postInt( $_key, $_default=false, $allow_nagative=false )
    {
        if ( ! isset( $_POST[$_key] ) ) return $_default;
        
        $v    = intval($_POST[$_key]);
        if ( $v < 0 && $allow_nagative == false )
        {
            return false;
        }

        return $v;
    }
    
    /**
     * fetch item from $_POST with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function postModel( $_model, &$_errno )
    {
        //check the load status of Filter class
        self::checkAndLoadFilter();
        
        return Filter::loadFromModel($_POST, $_model, $_errno);
    }
    
    //----------------------------------------------------------
    
    /**
     * fetch item from $_COOKIE data source
     *
     * @param    $_key
     * @param    $_model
     * @param    $_default
     * @param    $_errno
     * @return    Mixed
    */
    public function cookie( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_COOKIE[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_COOKIE, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_COOKIE[$_key];
    }
    
    /**
     * Fetch an integer form $_COOKIE global array
     *
     * @param    $_key
     * @param    $_default
     * @param    $allow_nagative
     * @return    Mixed(Integer or false)
    */
    public function cookieInt( $_key, $_default=false, $allow_nagative=false )
    {
        if ( ! isset( $_COOKIE[$_key] ) ) return $_default;
        
        $v    = intval($_COOKIE[$_key]);
        if ( $v < 0 && $allow_nagative == false )
        {
            return false;
        }

        return $v;
    }
    
    /**
     * fetch item from $_COOKIE with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function cookieModel( $_model, &$_errno )
    {
        //check the load status of Filter class
        self::checkAndLoadFilter();
            
        return Filter::loadFromModel($_COOKIE, $_model, $_errno);
    }
    
    //----------------------------------------------------------
    
    /**
     * fetch item from $_SESSION data source
     *
     * @param    $_key
     * @param    $_model
     * @param    $_default
     * @param    $_errno
     * @return    Mixed
    */
    public function session( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_SESSION[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_SESSION, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_SESSION[$_key];
    }
    
    //----------------------------------------------------------
    
    /**
     * fetch item from $_REQUEST data source
     *
     * @param    $_key
     * @param    $_model
     * @param    $_default
     * @param    $_errno
     * @return    Mixed
    */
    public function request( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_REQUEST[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_REQUEST, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_REQUEST[$_key];
    }
    
    /**
     * Fetch an integer form $_REQUEST global array
     *
     * @param    $_key
     * @param    $_default
     * @param    $allow_nagative
     * @return    Mixed(Integer or false)
    */
    public function requestInt( $_key, $_default=false, $allow_nagative=false )
    {
        if ( ! isset( $_REQUEST[$_key] ) ) return $_default;
        
        $v    = intval($_REQUEST[$_key]);
        if ( $v < 0 && $allow_nagative == false )
        {
            return false;
        }

        return $v;
    }
    
    /**
     * fetch item from $_REQUEST with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function requestModel( $_model, &$_errno )
    {
        //check the load status of Filter class
        self::checkAndLoadFilter();
            
        return Filter::loadFromModel($_REQUEST, $_model, $_errno);
    }
    
    //----------------------------------------------------------
    
    /**
     * fetch item from $_SERVER data source
     *
     * @param    $_key
     * @param    $_model
     * @param    $_default
     * @param    $_errno
     * @return    Mixed
    */
    public function server( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_SERVER[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_SERVER, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_SERVER[$_key];
    }
    
    //---------------------------------------------------------
    
    /**
     * fetch item from $_SERVER data source
     *
     * @param    $_key
     * @param    $_model
     * @param    $_default
     * @param    $_errno
     * @return    Mixed
    */
    public function env( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_ENV[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL )
        {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_ENV, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_ENV[$_key];
    }
}
?>
