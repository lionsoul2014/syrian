<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Json view manager class
 *      base on json_decode/json_encode
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
    }
    
    /**
     * return the content
     *
     * @param   $_tpl_file
     * @return  string the executed html text
    */
	public function getContent( $_tpl_file = NULL )
    {
        return json_encode( $this->_data );
    }
}

?>