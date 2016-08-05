<?php
/*
 * View parser class common interface.
 * 
 * @author    chenxin<chenxin619315@gmail.com>
 */
abstract class AView
{
    /**
     * symtab table:
     * Template symtab hold all the mapping 
     *  added from interface #assign, #assoc, #load
     *
     * @access  protected
    */
    protected $_symbol = array();

    /**
     * Assign a mapping to the view
     *
     * @param   $_name
     * @param   $_value
     * @return  Object AView
    */
    public function assign( $_name, $_value )
    {
        $this->_symbol[$_name] = &$_value;
        
        return $this;
    }
    
    /**
     * associate a mapping with the specified name
     *  to $_value, and $_name is just a another quote of $_value
     *
     * @param   $_name
     * @param   $_value
     * @param   Object AView
    */
    public function assoc( $_name, &$_value )
    {
        $this->_symbol[$_name] = &$_value;
        
        return $this;
    }
    
    /**
     * Load data from a array, take the key as the new key
     *  and the value as the new value.
     *
     * @param   $_array
     * @param   Object AView
    */
    public function load( $_array )
    {
        if ( ! empty($_array) ) {
            $this->_symbol = array_merge($this->_symbol, $_array);
        }
            
        return $this;
    }

    /**
     * get the value mapping with the specified key
     *
     * @param   $key
     * @return  Mixed
    */
    public function get($key)
    {
        return isset($this->_symbol[$key]) ? $this->_symbol[$key] : null;
    }

    /**
     * check the existence of the specified mapping
     *
     * @param   $key
     * @return  bool
    */
    public function has($key)
    {
        return isset($this->_symbol[$key]);
    }

    /**
     * sanitize the executed view content
     *
     * @param   $ret
     * @return  String
     */
    protected function sanitize($ret)
    {
        static $_rules = array(
            //'/\/\/[^\n]*?\n{1,}/'    => '',
            '/\n{1,}/'  => '',
            '/\s{2,}/'  => ' '    //@Note: not empty string here
        );

        return preg_replace(array_keys($_rules), $_rules, $ret);
    }

    /**
     * get the executed result by the implemented template engine
     *
     * @param   $_tpl_file
     * @param   $sanitize
    */
    public function getContent( $_tpl_file=NULL, $sanitize=false ) {}
}

 //------------------------------------------------------------------

/**
 * View parse factory
 *  Quick way to lanch all kinds of view with just a key
 * like: Html, Json, Xml, eg..
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

class ViewFactory
{
    private static $_classes = NULL;
    
    /**
     * Load and create the instance of a specified db class
     *      with a specified key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
    */
    public static function create( $_class, &$_conf=NULL )
    {
        if ( self::$_classes == NULL ) self::$_classes = array();
        
        $_class = ucfirst( $_class ) . 'View';
        if ( ! isset(self::$_classes[$_class]) ) {
            require __DIR__ . "/{$_class}.class.php";
            self::$_classes[$_class] = true;
        }
        
        return new $_class($_conf);
    }

}
?>
