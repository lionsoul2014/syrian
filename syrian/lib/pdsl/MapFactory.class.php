<?php
/**
 * Map common interface
 *
 * @author chenxin<chenxin619315@gmail.com>
*/

interface IMap
{    
    public function setKey($key);
    public function setTtl($ttl);
    public function keys();
    public function values();
    public function size();
    public function remove();

    public function get($key, $callback=null);
    public function mget($key_arr, $callback=null);

    public function set($key, $value);
    public function setNx($key, $val);
    public function mset($key, $val_arr);
    public function incBy($key, $i_val);
    public function incByFloat($key, $f_val);

    public function exists($key);
    public function del($key);
    public function close();
}

 //----------------------------------------------------

/**
 * Dynamic content map factory
 *      Quick way to lanch all kinds of map with just a key
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-----------------------------------------------------

class MapFactory
{
    /**
     * all the loaded classed
     *
     * @access  private
    */
    private static $_classes = array();
    
    /**
     * Load and create the instance of a specifield List class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
    */
    public static function create($_class, $_conf=null)
    {
        //Fetch the class
        $_class = ucfirst( $_class ) . 'Map';
        if ( ! isset( self::$_classes[$_class] ) ) {
            require  dirname(__FILE__) ."/{$_class}.class.php";
            self::$_classes[$_class] = true;
        }
        
        //return the newly created instance
        return new $_class($_conf);
    }
}
