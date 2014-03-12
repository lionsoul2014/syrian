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
   //input method and data source mapping
   private $_mapping = NULL;
   
   //input method and instance mapping
   private $_inputs = array(
	  'get'           => NULL,
	  'post'          => NULL,
	  'session'       => NULL,
	  'cookie'        => NULL,
	  'request'       => NULL,
	  'server'        => NULL,
	  'env'           => NULL
   );
   
   
   /**
	* construct method
	*      initialize the source of all inputs 
   */
   public function __construct()
   {
	  $this->_mapping = new stdClass();
	  //input method and data source mapping
	  $this->_mapping->get 		= &$_GET;
	  $this->_mapping->post		= &$_POST;
	  $this->_mapping->session	= &$_SESSION;
	  $this->_mapping->cookie	= &$_COOKIE;
	  $this->_mapping->request	= &$_REQUEST;
	  $this->_mapping->server	= &$_SERVER;
	  $this->_mapping->env		= &$_ENV;
   }
   
   /**
	* class attributes access interceptor
	*
	* @param   $_key
	* @return  Object
   */
   public function __get( $_key )
   {
	  if ( ! isset($this->_mapping->{$_key}) )
	  {
		 return;
	  }
	  
	  //check and create the specifile input source
	  if ( $this->_inputs[$_key] == NULL )
	  {
		 //echo $_key . ', initialized';
		 //var_dump($this->_mapping->{$_key});
		 $this->_inputs[$_key] = new InputSource($this->_mapping->{$_key});
	  }
	   
	  //return the instance
	  return $this->_inputs[$_key];
   }
}


/**
 * Input Source class
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class InputSource
{
   private static $_filterLoaded = false;	//flag of load the Filter class
   
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
   public function getString( $_key )
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