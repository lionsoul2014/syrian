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
	public function batchInsert( $_table, &$_array );
	public function delete( $_table, $_where );
	public function getList( $_query, $_type = NULL );
	public function getOneRow( $_query, $_type = NULL );
	public function update( $_table, &$_array, $_where, $_quote = true );
	public function getRowNum( $_query, $_res = false );
	public function count( $_table, $_field = 0, $_where = NULL, $_group = NULL );
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
	//class load cache pool
	private static $_classes 	= NULL;

	//instance create cache pool
	private static $POOL 		= NULL;
	
	/**
     * Load and create the instance of a specifield db class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
     * @param 	$cache 	wether to cache the db instance
    */
	public static function create( $_class, &$host, $cache = true )
	{
		if ( self::$_classes == NULL ) 		 self::$_classes = array();
		if ( $cache && self::$POOL == NULL ) self::$POOL 	 = array();

		/*
		 * Idb instance cache pool, we will cache the instance, so
	 	 *	use the cache instance instead when the aim server(port) 
	 	 *		and database is connect ever this will save a lot resource
	 	 *	to start a TCP/IP connection...........
	 	 * 
	 	 * @added 	2014-04-17
		*/
		$key 		= &$host['serial'];
		if ( $cache && isset(self::$POOL[$key]) ) return self::$POOL[$key];


		//-------------------------------------------------------
		//yat, fetch the class
		$_class = ucfirst( $_class );
		if ( ! isset( self::$_classes[$_class] ) )
		{
			require dirname(__FILE__).'/'.$_class.'.class.php';
			self::$_classes[$_class] = true;
		}
		
		//create a new db instance cache it
		$instance 	= new $_class($host);
		if ( $cache ) self::$POOL[$key]	= &$instance;

		return $instance;
	}
}
?>
