<?php
class Postgresql implements Idb
{
	public function insert( $_table, &$_array ) {}
	public function delete( $_table, $_where ) {}
	public function getList( $_query ) {}
	public function getOneRow( $_query ) {}
	public function update( $_table, &$_array, $_where ) {}
	public function getRowNum( $_query, $_res = false ) {}
	public function count( $_table, $_field, $_where = NULL ) {}
}
?>