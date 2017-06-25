<?php
/**
 * Dynamic queue common interface
 *
 * @author will<pan.kai@icloud.com>
 */
interface IQueue
{
    public function put($key, $value);
    public function set($key, $value);  // set() is an alias of put()
    public function get($key);
    public function delete($key);
    public function first();
    public function shift();
    public function pop();
    public function last();
    public function close();
}

class QueueFactory
{
    /**
     * all the loaded classed
     *
     * @access  private
     */
    private static $_classes = array();

    /**
     * Load and create the instance of a specifield queue class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
     */
    public static function create($_class, $_conf=NULL)
    {
        //Fetch the class
        $_class = ucfirst( $_class ) . 'Queue';
        if ( ! isset( self::$_classes[$_class] ) ) {
            require  dirname(__FILE__) ."/{$_class}.class.php";
            self::$_classes[$_class] = true;
        }

        //return the newly created instance
        return new $_class($_conf);
    }
}