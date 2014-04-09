<?php
/*
 * database handling class common interface.
 * 
 * @author	chenxin<chenxin619315@gmail.com>
 */
interface Idb
{
	public function execute( $_sql );
	public function insert( $_table, &$_array );
	public function delete( $_table, $_where );
	public function getList( $_query, $_type = NULL );
	public function getOneRow( $_query, $_type = NULL );
	public function update( $_table, &$_array, $_where, $_quote = true );
	public function getRowNum( $_query, $_res = false );
	public function count( $_table, $_field = 0, $_where = NULL );
}

 //------------------------------------------------------------------

/**
 * Database handling instance factory class
 * 	Quick way to lanch all kinds of DBMS client with just a key
 * like: Mysql, Postgresql, Oracle, eg...
 *
 * @author	chenxin<chenxin619315@gmail.com>
*/

 //-------------------------------------------------------------------

defined('SY_DB_DEBUG') or define('SY_DB_DEBUG', false);

class DbFactory
{
	private static $_classes = NULL;
	
	/**
     * Load and create the instance of a specifield db class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
    */
	public static function create( $_class, &$_host )
	{
		if ( self::$_classes == NULL ) self::$_classes = array();
		
		//Fetch the class
		$_class = ucfirst( $_class );
		if ( ! isset( self::$_classes[$_class] ) )
		{
			require dirname(__FILE__).'/'.$_class.'.class.php';
			self::$_classes[$_class] = true;
		}
		
		return new $_class($_host);
	}
}
?>