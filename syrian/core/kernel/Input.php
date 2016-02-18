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
defined('OP_NULL')      or define('OP_NULL',        1 <<  0);
defined('OP_LATIN')     or define('OP_LATIN',       1 <<  1);
defined('OP_URL')       or define('OP_URL',         1 <<  2);
defined('OP_EMAIL')     or define('OP_EMAIL',       1 <<  3);
defined('OP_QQ')        or define('OP_QQ',          1 <<  4);
defined('OP_DATE')      or define('OP_DATE',        1 <<  5);
defined('OP_NUMERIC')   or define('OP_NUMERIC',     1 <<  6);
defined('OP_STRING')    or define('OP_STRING',      1 <<  7);
defined('OP_ZIP')       or define('OP_ZIP',         1 <<  8);
defined('OP_CELLPHONE') or define('OP_CELLPHONE',   1 <<  9);
defined('OP_TEL')       or define('OP_TEL',         1 << 10);
defined('OP_IDENTIRY')  or define('OP_IDENTIRY',    1 << 11);

//santilize type
defined('OP_SANITIZE_TRIM')     or define('OP_SANITIZE_TRIM',   1 << 0);
defined('OP_SANITIZE_SCRIPT')   or define('OP_SANITIZE_SCRIPT', 1 << 1);
defined('OP_SANITIZE_HTML')     or define('OP_SANITIZE_HTML',   1 << 2);
defined('OP_MAGIC_QUOTES')      or define('OP_MAGIC_QUOTES',    1 << 3);
defined('OP_SANITIZE_INT')      or define('OP_SANITIZE_INT',    1 << 4);
defined('OP_LOWERCASE')         or define('OP_LOWERCASE',       1 << 5);
defined('OP_UPPERCASE')         or define('OP_UPPERCASE',       1 << 6);

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
        if ( self::$_loaded == false ) {
            Loader::import('Filter');
            self::$_loaded = true;
        }
    }

    /**
     * string to boolean
     * string true, integer larger than 0 will be consider to true or false 
     *
     * @param   $val
     * @return  boolean
    */
    public static function string2Boolean($val)
    {
        if ( is_bool($val) ) {
            return $val;
        } else if ( is_string($val) ) {
            $val = strtolower($val);
            if ( $val == 'true' ) return true;
            else if ( $val == 'false' ) return false;
            else {
                $i = intval($val);
                return $i > 0 ? true : false;
            }
        } else if ( is_integer($val) ) {
            return $val > 0 ? true : false;
        }

        return false;
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
        if ( $_model != NULL ) {
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
        
        $v = intval($_GET[$_key]);
        if ( $v < 0 && $allow_nagative == false ) {
            return false;
        }

        return $v;
    }

    /**
     * Fetch an boolean from $_GET global array
     *
     * @param   $_key
     * @return  boolean
    */
    public function getBoolean($_key)
    {
        if ( ! isset($_GET[$_key]) ) return false;
        return self::string2Boolean($_GET[$_key]);
    }

    /**
     * Fetch an Id from $_GET global array
     * 24-chars and 32 chars unique id
     *
     * @param   $_key
     * @return  Mixed String or false
    */
    public function getUID($_key, $default=false)
    {
        if ( ! isset($_GET[$_key]) ) return $default;

        $v   = $_GET[$_key];
        $len = strlen($v);
        if ( $len != 24 && $len != 32 ) return false;
        if ( self::isValidUid($v) == false ) return false;

        return $v;
    }
    
    /**
     * fetch item from $_GET with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function getModel( $_model, &$_errno=NULL )
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
        if ( $_model != NULL ) {
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
        
        $v = intval($_POST[$_key]);
        if ( $v < 0 && $allow_nagative == false ) {
            return false;
        }

        return $v;
    }

    /**
     * Fetch an boolean from $_POST global array
     *
     * @param   $_key
     * @return  boolean
    */
    public function postBoolean($_key)
    {
        if ( ! isset($_POST[$_key]) ) return false;
        return self::string2Boolean($_POST[$_key]);
    }
    
    /**
     * Fetch an Id from $_POST global array
     * 24-chars and 32 chars unique id
     *
     * @param   $_key
     * @return  Mixed String or false
    */
    public function postUID($_key, $default=false)
    {
        if ( ! isset($_POST[$_key]) ) return $default;

        $v   = $_POST[$_key];
        $len = strlen($v);
        if ( $len != 24 && $len != 32 ) return false;
        if ( self::isValidUid($v) == false ) return false;

        return $v;
    }
    
    /**
     * fetch item from $_POST with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function postModel( $_model, &$_errno=NULL )
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
        if ( $_model != NULL ) {
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
        
        $v = intval($_COOKIE[$_key]);
        if ( $v < 0 && $allow_nagative == false ) {
            return false;
        }

        return $v;
    }

    /**
     * Fetch an boolean from $_COOKIE global array
     *
     * @param   $_key
     * @return  boolean
    */
    public function cookieBoolean($_key)
    {
        if ( ! isset($_COOKIE[$_key]) ) return false;
        return self::string2Boolean($_COOKIE[$_key]);
    }

    /**
     * Fetch an Id from $_COOKIE global array
     * 24-chars and 32 chars unique id
     *
     * @param   $_key
     * @return  Mixed String or false
    */
    public function cookieUID($_key, $default=false)
    {
        if ( ! isset($_COOKIE[$_key]) ) return $default;

        $v   = $_COOKIE[$_key];
        $len = strlen($v);
        if ( $len != 24 && $len != 32 ) return false;
        if ( self::isValidUid($v) == false ) return false;

        return $v;
    }
    
    /**
     * fetch item from $_COOKIE with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function cookieModel( $_model, &$_errno=NULL )
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
        if ( $_model != NULL ) {
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
        if ( $_model != NULL ) {
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
        if ( $v < 0 && $allow_nagative == false ) {
            return false;
        }

        return $v;
    }

    /**
     * Fetch an boolean from $_REQUEST global array
     *
     * @param   $_key
     * @return  boolean
    */
    public function requestBoolean($_key)
    {
        if ( ! isset($_REQUEST[$_key]) ) return false;
        return self::string2Boolean($_REQUEST[$_key]);
    }

    /**
     * Fetch an Id from $_REQUEST global array
     * 24-chars and 32 chars unique id
     *
     * @param   $_key
     * @return  Mixed String or false
    */
    public function requestUID($_key, $default=false)
    {
        if ( ! isset($_REQUEST[$_key]) ) return $default;

        $v   = $_REQUEST[$_key];
        $len = strlen($v);
        if ( $len != 24 && $len != 32 ) return false;
        if ( self::isValidUid($v) == false ) return false;

        return $v;
    }
    
    /**
     * fetch item from $_REQUEST with a specifiel model
     *
     * @param    $_model
     * @param    $_errno
     * @return    Mixed
    */
    public function requestModel( $_model, &$_errno=NULL )
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
     * @return   Mixed
    */
    public function server( $_key, $_model=NULL, $_default=false, &$_errno=NULL )
    {
        if ( ! isset($_SERVER[$_key]) ) return $_default;
        
        //apply the model if it is not null
        if ( $_model != NULL ) {
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
        if ( $_model != NULL ) {
            //check the load status of Filter class
            self::checkAndLoadFilter();
            
            return Filter::get( $_ENV, $_key, $_model, $_errno );
        }
        
        //normal string fetch
        return $_ENV[$_key];
    }


    //-----------------------------------------------------

    /**
     * check the specifiled string is made of ascii char
     * Only chars from a-z or 0-9
     *
     * @param   $string
     * @return  boolean
    */
    private static function isValidUid($string)
    {
        $len = strlen($string);
        for ( $i = 0; $i < $len; $i++ ) {
            $ascii = ord($string[$i]);
            if ( $ascii >= 48 && $ascii <= 57 )  continue;
            if ( $ascii >= 97 && $ascii <= 122 ) continue;
            return false;
        }

        return true;
    }

}
?>
