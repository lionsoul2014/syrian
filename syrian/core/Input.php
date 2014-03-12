<?php if ( ! defined('APPPATH') ) exit('No Direct Access Allowed!');
/**
 * Syrian Input Manager Class
 * Offer interface to:
 * 
 * 1. Quick lanch the input source
 * 2. Data type check and convertor
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

 //--------------------------------------------------------------
 
 class Input
 {
    private $_inputs = array(
        'get'           => NULL,
        'post'          => NULL,
        'session'       => NULL,
        'cookie'        => NULL,
        'request'       => NULL,
        'server'        => NULL,
        'env'           => NULL,
    );
    
    
    /**
     * construct method
     *      initialize the source of all inputs 
    */
    public function __construct()
    {
        $this->_inputs['get']       = &$_GET;
        $this->_inputs['post']      = &$_POST;
        $this->_inputs['session']   = &$_SESSION;
        $this->_inputs['cookie']    = &$_COOKIE;
        $this->_inputs['request']   = &$_REQUEST;
        $this->_inputs['server']    = &$_SERVER;
        $this->_inputs['env']       = &$_ENV;
    }
    
    /**
     * class attributes access interceptor
     *
     * @param   $_key
     * @return  Object
    */
    public function __get( $_key )
    {
        if ( ! isset($this->_inputs[$_key]) )
        {
            return;
        }
        
        //check and create the specifile input source
        $o = $this->_inputs[$_key];
        if ( is_array($o) )
        {
            $o = new InputSource($this->_inputs[$_key]);
        }
        
        return $o;
    }
 }
 
 
 /**
  * Input Source class
  *
  * @author chenxin <chenxin619315@gmail.com>
 */
 class InputSource
 {
    private static $_filterLoaded = false;
    
    private $_source = NULL;                //input source
    
	public function __construct( &$_source )
    {
        $this->_source = &$_source;
    }
    
    /**
     * get int from current input
     *
     * @param   $_key
     * @return  Mixed(Integer or false)
    */
    public function getInt( $_key )
    {
        if ( ! isset( $this->_source[$_key] ) )
            return false;
        
        return intval($this->_source[$_key]);
    }
    
    /**
     * normally return the value mapping with the specifile key
     *
     * @param   $_key
     * @return  Mixed(string, false)
    */
    public function get( $_key )
    {
        if ( isset($this->_source[$_key]) )
        {
            return $this->_source[$_key];
        }
        
        return false;
    }
    
    /**
     * get a argument filter with a specifial filter model
     *
     * @param   $_key
     * @return  Mixed(String or false)
    */
    public function getModel( $_key, $_model )
    {
        //check and load the filter class
        if ( self::$_filterLoaded == false )
        {
            import('util.filter.Filter');
        }
        
        return Filter::get( $this->_source, $_key, $_model, $_errno );
    }
 }
?>