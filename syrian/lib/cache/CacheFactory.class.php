<?php
/**
 * Dynamic content cache common interface
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
interface ICache
{
    public function get( $baseId, $_factor, $_time );
    public function set( $baseId, $_factor, $_content );
}

 //----------------------------------------------------

/**
 * Dynamic content cache factory
 *      Quick way to lanch all kinds of cache with just a key
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-----------------------------------------------------

class CacheFactory
{
    private static $_classes = NULL;
    
    /**
     * Load and create the instance of a specifield cache class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
    */
    public static function create( $_class, $_conf = NULL )
    {
        if ( self::$_classes == NULL ) self::$_classes = array();
        
        //Fetch the class
        $_class = ucfirst( $_class ) . 'Cache';
        if ( ! isset( self::$_classes[$_class] ) )
        {
            require  dirname(__FILE__) .'/'.$_class.'.class.php';
            self::$_classes[$_class] = true;
        }
        
        //return the newly created instance
        return new $_class($_conf);
    }
}
?>
