<?php
/**
 * dynamic content cache factory .
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

//-----------------------------------------------------

define('_CACHE_HOME_', dirname(__FILE__));

class CacheFactory
{

    private static $_classes = NULL;
    
    public static function create( $_class, $_args = NULL )
    {
        if ( self::$_classes == NULL ) {
            self::$_classes = array();
            //require the common interface
            require _CACHE_HOME_.'/ICache.class.php';
        }
        
        $_class = ucfirst( $_class ).'Cache';
        if ( ! isset( self::$_classes[$_class] ) ) {
            require _CACHE_HOME_.'/'.$_class.'.class.php';
            self::$_classes[$_class] = true;
        }
        
        //return the newly created object
        return new $_class($_args);
     }
}
?>