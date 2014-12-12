<?php
/**
 * simple elasticsearch php client
 *
 * @author	chenxin<chenxin619315@gmail.com>
 */

 //--------------------------------------------------


class ElasticSearch 
{
	/**
	 * default elasticsearch http server host and port
	 *
	 * @access	private
	 */
	private	$_host = 'localhost';
	private $_port = 9200;

	/**
	 * default elasticsearch index name and type name
	 */
	private $_index = NULL;
	private $_type = NULL;

	public function __construct( $conf )
	{
		if ( isset($conf['host']) )	$this->_host = $conf['host'];
		if ( isset($conf['port']) )	$this->_port = $conf['port'];

		if ( isset($conf['index']) )	$this->_index = $conf['index'];
		if ( isset($conf['type']) )		$this->_type = $conf['type'];
	}

	//set the index
	public function index( $index )
	{
		$this->_index = $index;

		return $this;
	}

	//set the type
	public function type( $type )
	{
		$this->_type = $type;

		return $this;
	}

	//set the port
	public function port( $port )
	{
		$this->_port = $port;
	}

	/*
	 * do elasticsearch query
	 */
	public function do_query( $method, $json, $id=NULL, $conf=NULL )
	{
		$_index		= isset($conf['index']) ? $conf['index'] : $this->_index;
		$_type		= isset($conf['type'])	? $conf['type'] : $this->_type;

		if ( $_index == NULL ) return false;

		$url	= "http://{$this->_host}:{$this->_port}/{$_index}";
		if ( $_type != NULL )	$url .= "/{$_type}";
		if ( $id != NULL )		$url .= "/{$id}";

		$methods 	= array(
			"PUT" 		=> 1,
			"DELETE" 	=> 2, 
			"GET"		=> 3,
			"POST"		=> 4
		);
		$method		= strtoupper($method);
		if ( ! isset($methods[$method]) ) return false;

		//format the data
		$ch		= curl_init();

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if ( $json != NULL ) curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$ret	= curl_exec($ch);
		curl_close($ch);

		if ( $ret == false ) return false;
		$json	= json_decode($ret);
		if ( $json == NULL ) return false;

		return $json;
	}

	/**
	 * add record to the index or update the recrod
	 *
	 * @json	json data
	 * @id		id or could be _mapping or _setting
	 */
	public function add( $json, $id=NULL )
	{
		if ( $this->_index == NULL ) return false;

		$json	= $this->do_query('PUT', $json, $id);

		//{"_index":"stream","_type":"main","_id":"1","_version":2,"created":false}

		if ( $json == false ) return false;
		return isset($json->_version) ? $json->_version : false;
	}

	/**
	 * remove index
	 *
	 * @Note: 
	 * remove the whole index by just set the index and keep the type = NULL
	 * remove the specifield type by just set the index and the type
	 * remove the specifield record by set pass the id
	 *
	 * @param	$id
	 */
	public function remove( $id=NULL )
	{
		if ( $this->_index == NULL ) return false;

		$json	= $this->do_query("DELETE", NULL, $id);

		//{"found":false,"_index":"stream","_type":"main","_id":"1","_version":10}

		if ( $json == false ) return false;
		return isset($json->found) ? $json->found : false;
	}

	/**
	 * fetch the content associal with the specifield id
	 *
	 * @param	$id valid id
	 */
	public function queryById( $id )
	{
		if ( $this->_index == NULL || $this->_type == NULL ) return false;
		
		$json	 = $this->do_query("GET", NULL, $id);

		//{"_index":"stream","_type":"main","_id":"1","_version":2,"found":true,"_source":{}}

		if ( $json == false ) return false;
		if ( ! isset($json->found) || $json->found == false ) return false;
		return $json->_source;
	}

	/**
	 * do a elasticsearch query
	 *
	 * @param	$query elasticsearch json query string
	 * @param	$r_fields returning fields
	 */
	public function query( $query, $r_fields )
	{
		if ( $this->_index == NULL ) return false;

		$json	 = $this->do_query("POST", $query, '_search?fields='.$r_fields);

		//check if is a validate json object
		if ( $json == false ) return false;
		if ( ! isset($json->hits) ) 	return false;

		//take the took out
		$json->hits->took = $json->took;
		return $json->hits;
	}
}
?>
