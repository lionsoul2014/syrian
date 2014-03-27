<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Json Viewer manager class
 *      base on function json_decode/json_encode
 *
 * change the handler function throught $_config['encode'=>, 'decode'=>]
 *      --Not implemented yet...
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //---------------------------------------------------------
 
class JsonView implements IView
{
    /**
     * store all the mapping of assign or assco
     *
     * @access  private
    */
    private     $_data = NULL;
    
    public function __construct( $_conf )
    {
        //Do nothing here for now
    }
    
    /**
     * Assign a mapping to the view
     *
     * @param   $_name
     * @param   $_value
    */
	public function assign( $_name, $_value )
    {
        $this->_data[$_name] = &$_value;
        
        return $this;
    }
    
    /**
     * associate a mapping with the specifield name
     *  to $_value, and $_name is just a another quote of $_value
     *
     * @param   $_name
     * @param   $_value
    */
	public function assoc( $_name, &$_value )
    {
        $this->_data[$_name] = &$_value;
        
        return $this;
    }
    
    /**
     * Load data from a array, take the key as the new key
     *      and the value as the new value.
     *
     * @param   $_array
    */
    public function load( $_array )
    {
        if ( ! empty($_array) )
            $this->_data = array_merge($this->_data, $_array);
            
        return $this;
    }
    
    /**
     * return the content
     *
     * @param   $_tpl_file
     * @return  string the executed html text
    */
	public function getContent( $_output = NULL )
    {
        return json_encode( $this->_data );
    }
}
?>