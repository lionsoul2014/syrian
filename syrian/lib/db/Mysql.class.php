<?php
/**
 * mysql class offer common interface to handling
 * 		the database operation. <br />
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @version	1.2
 */
class Mysql implements Idb
{
	private $_link			= NULL;			//mysql connect resource
	private $_host			= NULL;			//connection information
	private $_escape		= true;
	
	/*connected to the database server and do some query work to unify the charset*/
	private function connect()
	{
		$this->_link = mysqli_connect($this->_host['host'], $this->_host['user'], 
					$this->_host['pass'], $this->_host['db'], $this->_host['port']);
		
		if ( $this->_link == FALSE ) die("Error: cannot connected to the database server.");
		
		$_charset = $this->_host['charset'];
		mysqli_query( $this->_link, 'SET NAMES \''.$_charset.'\'');
		mysqli_query( $this->_link, 'SET CHARACTER_SET_CLIENT = \''.$_charset.'\'');
		mysqli_query( $this->_link, 'SET CHARACTER_SET_RESULTS = \''.$_charset.'\'');
	}
	
	public function __construct( &$_host )
	{
		$this->_host = &$_host;
		
		//check the default magic quotes for GPC data
		$this->_escape = get_magic_quotes_gpc();
	}
	
	/**
	 * send an query string to the mysql server	<br />
	 * 
	 * @param	$_query
	 * @return	mixed
	 */
	private function query( &$_query )
	{
		//connect to the database server as necessary
		if ( $this->_link == NULL ) $this->connect();
		//print the query string for debug	
		if ( SY_DB_DEBUG ) echo "query: {$_query} <br/>\n" ;
		return mysqli_query( $this->_link, $_query );
	}
	
	/**
	 * Send the specifield sql string to the server
	 * 	and return the executed result as it is
	 *
	 * @param	$_sql
	 * @return	Mixed
	*/
	public function execute( $_sql )
	{
		return $this->query( $_sql );
	}
	
	/**
	 * insert the specified array into the database
	 * 
	 * @param	$_table
	 * @param	$_array
	 * @return	mixed
	 */
	public function insert( $_table, &$_array )
	{
		$_fileds = NULL;$_values = NULL;
		$_tval = NULL;
		
		foreach ( $_array as $_key => $_val )
		{
			$_tval = &$_val;
			if ( ! $this->_escape ) $_tval = addslashes($_val);
			
			$_fileds .= ( $_fileds==NULL ) ? $_key : ',' . $_key;
			$_values .= ( $_values==NULL ) ? '\''.$_tval.'\'' : ',\''.$_tval.'\'';
		}
		
		if ( $_fileds !== NULL )
		{
			$_query = 'INSERT INTO ' . $_table . '(' . $_fileds . ') VALUES(' . $_values . ')';
			if ( $this->query( $_query ) != FALSE ) return mysqli_insert_id( $this->_link );
		}
		
		return FALSE;
	}
	
	/**
	 * delete the specified record
	 * 
	 * @param	$_table
	 * @param	$_where
	 * @return	bool
	 */
	public function delete( $_table, $_where )
	{
		//for safe, where condition must needed
		$_query = 'DELETE FROM ' . $_table . ' WHERE '.$_where;
		if ( $this->query( $_query ) != FALSE )
			return mysqli_affected_rows($this->_link);
		return FALSE;
	}
	
	
	/**
	 * get a array list from the database
	 * 
	 * @param	$_query
	 * @param	$_type
	 * @return	mixed
	 */
	public function getList( $_query, $_type = MYSQLI_ASSOC )
	{
		$_ret = $this->query( $_query );
		
		if ( $_ret !== FALSE )
		{
			$_result = array();
			while ( ( $_row = mysqli_fetch_array( $_ret, $_type ) ) != FALSE )
				$_result[] = $_row;
			return $_result;
		}
		
		return FALSE;
	}
	
	/**
	 * update the specified records
	 * 		all the value will be quoted with ' punctuation for default, So
	 * 	you don't have to care about the data type of the fields
	 * 		in you aim database table, Or make $_quote false...
	 * 
	 * @param	$_table
	 * @param	$_array
	 * @param	$_where
	 * @param	$_quote
	 * @return	mixed
	 */
	public function update( $_table, &$_array, $_where, $_quote = true )
	{
		$_keys = NULL;
		$qstr = ($_quote) ? '\'' : "";
		$_tval = NULL;
		
		foreach ( $_array as $_key => $_val )
		{
			$_tval = &$_val;
			if ( ! $this->_escape ) $_tval = addslashes($_val);
			
			if ( $_keys == NULL ) $_keys = $_key."={$qstr}".$_tval."{$qstr}";
			else $_keys .= ','.$_key."={$qstr}".$_tval."{$qstr}";
		}
		
		if ( $_keys !== NULL )
		{
			$_query = 'UPDATE ' . $_table . ' SET ' . $_keys . ' WHERE '.$_where;
			/*
			 * return the query result directly
			 * Unlike the delete operation will make the affected rows available
			 *	TRUE for success and FALSE for failed
			*/
			return $this->query( $_query );
		}
		
		return FALSE;
	}
	
	/**
	 * get the specified record
	 *
	 * @param	$_query
	 * @return	mixed
	 */	
	public function getOneRow( $_query, $_type = MYSQLI_ASSOC )
	{
		$_ret = $this->query ( $_query );
		if ( $_ret != FALSE ) return mysqli_fetch_array( $_ret, $_type );
		return FALSE;
	}
	
	/**
	 * get row number of the specified query
	 * 
	 * @param	$_query
	 * @return	int
	 */
	public function getRowNum( $_query, $_res = false )
	{
		if ( $_res ) $_ret = $_res;
		else $_ret = $this->query( $_query );
		if ($_ret != FALSE) return mysqli_num_rows($_ret);
		return 0;
	}
	
	/**
	 * count the total records
	 *
	 * @param	$_fields
	 * @return	int
	*/
	public function count( $_table, $_field = 0, $_where = NULL )
	{
		$_query = 'SELECT count(' . $_field . ') FROM ' . $_table;
		if ( $_where != NULL ) $_query .= ' WHERE ' . $_where;
		if ( ($_ret = $this->getOneRow($_query, MYSQLI_NUM)) != FALSE )
			return $_ret[0];
		return 0;
	}
	
	public function __destruct()
	{
		if ( $this->_link != NULL ) mysqli_close( $this->_link );
	}
}
?>
