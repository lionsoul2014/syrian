<?php
class Oracle implements Idb
{
	public function execute( $_sql, $opt, $_row = false, $src = NULL ) {}
	public function insert( $_table, &$_array ) {}
	public function delete( $_table, $_where ) {}
	public function getList( $_query, $_type = NULL, $srw = NULL ) {}
	public function getOneRow( $_query, $_type = NULL, $srw = NULL ) {}
	public function update( $_table, &$_array, $_where ) {}
	public function getRowNum( $_query, $_res = false, $srw = NULL ) {}
	public function count( $_table, $_field, $_where = NULL, $_group = NULL, $srw = NULL ) {}
	public function setDebug( $_debug ) {}
	public function setSepRW( $srw ) {}
	public function slaveStrategy() {}
}
?>
