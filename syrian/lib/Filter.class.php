<?php
/**
 * GPC data filter class
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

//-----------------------------------------------------------

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
defined('OP_MOBILE')    or define('OP_MOBILE',      1 <<  9);
defined('OP_TEL')       or define('OP_TEL',         1 << 10);
defined('OP_IDENTIRY')  or define('OP_IDENTIRY',    1 << 11);

//santilize type
defined('OP_SANITIZE_TRIM')     or define('OP_SANITIZE_TRIM',       1 << 0);
defined('OP_SANITIZE_SCRIPT')   or define('OP_SANITIZE_SCRIPT',     1 << 1);
defined('OP_SANITIZE_HTML')     or define('OP_SANITIZE_HTML',       1 << 2);
defined('OP_MAGIC_QUOTES')      or define('OP_MAGIC_QUOTES',        1 << 3);
defined('OP_SANITIZE_INT')      or define('OP_SANITIZE_INT',        1 << 4);
defined('OP_LOWERCASE')         or define('OP_LOWERCASE',           1 << 5);
defined('OP_UPPERCASE')         or define('OP_UPPERCASE',           1 << 6);

if ( ! function_exists('OP_LIMIT') )
{
    function OP_LIMIT( $s, $e = -1 ) {return ( $e == -1 ? array(0, $s) : array(0, $s, $e) );}
}

if ( ! function_exists('OP_SIZE') )
{
    function OP_SIZE( $s, $e = -1 ) {return ($e == -1 ? array(1, $s) : array(1, $s, $e));}
}

class Filter
{
    public static function isLatin( $_val )
    {
        return (preg_match('/^[a-z0-9_]+$/i', $_val) == 1);
    }
    
    public static function isUrl( $_val )
    {
        return (filter_var($_val, FILTER_VALIDATE_URL) != false);
    }
    
    public static function isEmail( $_val )
    {
        return (filter_var($_val, FILTER_VALIDATE_EMAIL) != false);
    }
    
    public static function isQQ( $_val )
    {
        return (preg_match('/^[1-9][0-9]{4,14}$/', $_val) == 1);
    }
    
    public static function isDate( $_val )
    {
        return ( preg_match('/^[0-9]{4}-(0[1-9]|1[012])-([0][1-9]|[12][0-9]|[3][01])$/', $_val) == 1 );
    }
    
    //not all whitespace
    public static function isString( $_val )
    {
        return ($_val != '' && preg_match('/^\s{1,}$/', $_val) == 0);
    }
    
    public static function isZip( $_val )
    {
        return (preg_match('/^[0-9]{6}$/', $_val) == 1);
    }
    
    public static function isMobile( $_val )
    {
        return (preg_match('/^1[3|4|5|6|7|8|9][0-9]{9}$/', $_val) == 1);
    }
    
    public static function isTel( $_val )
    {
        return (preg_match('/^0[1-9][0-9]{1,2}-[0-9]{7,8}$/', $_val) == 1);
    }
    
    public static function isIdentity( $_val )
    {
        return (
            preg_match('/^[1-6][0-9]{5}[1|2][0-9]{3}(0[1-9]|10|11|12)([0|1|2][0-9]|30|31)[0-9]{3}[0-9A-Z]$/',
                $_val) == 1);
    }

    public static function sanitizeHtml( &$_val )
    {
        //sanitize regex rules
        $_rules = array(
            '/<[^>]*?\/>/is',
            '/<[^>]*?>.*?<\/[^>]*?>/is'
        );
        
        return preg_replace($_rules, '', $_val);
    }
    
    public static function sanitizeScript( &$_val )
    {
        //clear up the direct script.
        //clear up the onEvent of html node.
        $_rules = array(
            '/<script[^>]*?>.*?<\/script\s*>/i',
            '/<([^>]*?)\s+on[a-zA-Z]+\s*=\s*".*?"([^>]*?)>/i',
            '/<([^>]*?)\s+on[a-zA-Z]+\s*=\s*\'.*?\'([^>]*?)>/i'
        );

        return preg_replace($_rules, array('', '<$1$2>'), $_val);
    }
    
    public static function check( &$_val, $_model, &$_errno )
    {
        //1. data type check
        if ( $_val == NULL ) return '';
        
        $_errno = 0;
        if ( is_integer($_model[0]) ) {
            if ( ($_model[0] & OP_LATIN) != 0 && ! self::isLatin($_val) )       return false;
            if ( ($_model[0] & OP_URL) != 0 && ! self::isUrl($_val) )           return false;
            if ( ($_model[0] & OP_EMAIL) != 0 && ! self::isEmail($_val) )       return false;
            if ( ($_model[0] & OP_QQ) != 0 && ! self::isQQ($_val) )             return false;
            if ( ($_model[0] & OP_DATE) != 0 && ! self::isDate($_val) )         return false;
            if ( ($_model[0] & OP_NUMERIC) != 0 && ! is_numeric($_val) )        return false;
            if ( ($_model[0] & OP_STRING) != 0 && ! self::isString($_val) )     return false;
            if ( ($_model[0] & OP_ZIP) != 0 && ! self::isZip($_val) )           return false;
            if ( ($_model[0] & OP_MOBILE) != 0 && ! self::isMobile($_val) )     return false;
            if ( ($_model[0] & OP_TEL) != 0 && ! self::isTel($_val) )           return false;
            if ( ($_model[0] & OP_IDENTIRY) != 0 && ! self::isIdentity($_val) ) return false;
        } else if ( is_string($_model[0]) ) {
            if ( preg_match($_model[0], $_val) != 1 ) {
                return false;
            }
        }
        
        $_errno = 1;
        //2. data length check
        if ( isset($_model[1]) && $_model[1] != NULL ) {
            switch ( $_model[1][0] ) {
            case 0:             //length limit
                $_length = strlen($_val);
                if ( $_length < $_model[1][1] )     return false;
                if ( count($_model[1]) == 3 )
                    if ( $_length > $_model[1][2] ) return false;
                break;
            case 1:             //size limit
                if ( $_val < $_model[1][1] )        return false;
                if ( count($_model[1]) == 3 )
                    if ( $_val > $_model[1][2] )    return false;
                break;
            }
        }
        
        //3. sanitize
        if ( isset($_model[2]) ) {
            if ( $_model[2] == NULL ) return $_val;
            if ( ( $_model[2] & OP_SANITIZE_TRIM ) != 0 )
                $_val = trim($_val);
            if ( ( $_model[2] & OP_SANITIZE_SCRIPT ) != 0 )
                $_val = self::sanitizeScript($_val);
            if ( ( $_model[2] & OP_SANITIZE_HTML ) != 0 )
                $_val = self::sanitizeHtml($_val);
            if ( ( $_model[2] & OP_SANITIZE_INT ) != 0 )
                $_val = intval($_val);
            if ( ( $_model[2] & OP_MAGIC_QUOTES ) != 0
                && ini_get('magic_quotes_gpc') == 0 ) {
                $_val = addslashes($_val);
            }
            if ( ($_model[2] & OP_LOWERCASE) != 0 ) {
                $_val = strtolower($_val);
            }
            if ( ($_model[2] & OP_UPPERCASE) != 0 ) {
                $_val = strtoupper($_val);
            }
        }
            
        return $_val;
    }
    
    public static function get( $_src, $_name, $_model, &$_errno )
    {
        if ( ! isset($_src[$_name]) || $_src[$_name] == '' ) {
            $_value = NULL;
        } else {
            $_value = &$_src[$_name];
        }

        return self::check($_value, $_model, $_errno);
    }
    
    public static function filterVar( $_val, $_model, &$_errno )
    {
        return self::check($_val, $_model, $_errno);
    }
    
    public static function loadFromModel( $_src, $_model, &$_errno )
    {
        $_data = array();
        foreach ( $_model as $_name => $_value ) {
            $_ret = self::get($_src, $_name, $_value, $_errno);
            if ( $_ret === false ) {
                $_errno = array($_name, $_errno);
                return false;
            }
            
            //store the returnd item
            $_data[$_name] = $_ret;
        }
        
        return $_data;
    }
}
