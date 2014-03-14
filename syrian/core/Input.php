<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
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
	private static $_loaded = false;
	
	public function __construct()
	{
	   //Do nothing here
	}
	
	/**
	 * check and load the Filter class if it is not load
	 *
	 * @see	lib.util.filter.Filter
	*/
	private static function checkAndLoadFilter()
	{
		//check the load status of Filter class
		if ( self::$_loaded == false )
		{
			//echo 'Filter class loaded';
			Loader::import('Filter');
			self::$_loaded = true;
		}
	}
   
   /**
    * fetch item from $_GET data source
    *
    * @param	$_key
    * @param	$_model
    * @param	$_errno
    * @return	Mixed(Array, String, Bool)
   */
	public function get( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset( $_GET[$_key] ) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_GET, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_GET[$_key];
	}
	
	/**
	 * fetch item from $_GET with a specifiel model
	 *
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function getModel( $_model, &$_errno )
	{
		//check the load status of Filter class
		self::checkAndLoadFilter();
			
		return Filter::loadFromModel($_GET, $_model, $_errno);
	}
	
	//----------------------------------------------------------
	
	/**
	 * fetch item from $_POST data source
	 *
	 * @param	$_key
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function post( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset($_POST[$_key]) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_POST, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_POST[$_key];
	}
	
	/**
	 * fetch item from $_POST with a specifiel model
	 *
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function postModel( $_model, &$_errno )
	{
		//check the load status of Filter class
		self::checkAndLoadFilter();
		
		return Filter::loadFromModel($_POST, $_model, $_errno);
	}
	
	//----------------------------------------------------------
	
	/**
	 * fetch item from $_COOKIE data source
	 *
	 * @param	$_key
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function cookie( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset($_COOKIE[$_key]) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_COOKIE, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_COOKIE[$_key];
	}
	
	/**
	 * fetch item from $_COOKIE with a specifiel model
	 *
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function cookieModel( $_model, &$_errno )
	{
		//check the load status of Filter class
		self::checkAndLoadFilter();
			
		return Filter::loadFromModel($_COOKIE, $_model, $_errno);
	}
	
	//----------------------------------------------------------
	
	/**
	 * fetch item from $_SESSION data source
	 *
	 * @param	$_key
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function session( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset($_SESSION[$_key]) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_SESSION, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_SESSION[$_key];
	}
	
	//----------------------------------------------------------
	
	/**
	 * fetch item from $_REQUEST data source
	 *
	 * @param	$_key
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function request( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset($_REQUEST[$_key]) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_REQUEST, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_REQUEST[$_key];
	}
	
	/**
	 * fetch item from $_REQUEST with a specifiel model
	 *
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function requestModel( $_model, &$_errno )
	{
		//check the load status of Filter class
		self::checkAndLoadFilter();
			
		return Filter::loadFromModel($_REQUEST, $_model, $_errno);
	}
	
	//----------------------------------------------------------
	
	/**
	 * fetch item from $_SERVER data source
	 *
	 * @param	$_key
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function server( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset($_SERVER[$_key]) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_SERVER, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_SERVER[$_key];
	}
	
	//---------------------------------------------------------
	
	/**
	 * fetch item from $_SERVER data source
	 *
	 * @param	$_key
	 * @param	$_model
	 * @param	$_errno
	 * @return	Mixed
	*/
	public function env( $_key, $_model = NULL, &$_errno = NULL )
	{
		if ( ! isset($_ENV[$_key]) ) return false;
		
		//apply the model if it is not null
		if ( $_model != NULL )
		{
			//check the load status of Filter class
			self::checkAndLoadFilter();
			
			return Filter::get( $_ENV, $_key, $_model, $_errno );
		}
		
		//normal string fetch
		return $_ENV[$_key];
	}
}
?>