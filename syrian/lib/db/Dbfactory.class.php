<?php
/**
Database handling instance factory class . <br />

@author	chenxin<chenxin619315@gmail.com>
@see	Idb.class.php
*/
defined('DB_LIB_HOME')	or	define('DB_LIB_HOME', dirname(__FILE__) . DIRECTORY_SEPARATOR);

class Dbfactory
{
	private static $_classes = NULL;
	
	public static function create( $_class, &$_host )
	{
		if ( self::$_classes == NULL )
		{
			self::$_classes = array();
			require DB_LIB_HOME . 'Idb.class.php';
		}
		
		$_class = ucfirst( $_class );
		if ( ! isset( self::$_classes[$_class] ) )
		{
			require DB_LIB_HOME . $_class . '.class.php';
			self::$_classes[$_class] = true;
		}
		
		return new $_class($_host);
	}
}
?>