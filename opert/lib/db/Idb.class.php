<?php
/*
 * database handling class common interface.
 * 
 * @author	chenxin<chenxin619315@gmail.com>
 */
interface Idb
{
	public function insert( $_table, &$_array );
	public function delete( $_table, $_where );
	public function getList( $_query, $_type = NULL );
	public function getOneRow( $_query, $_type = NULL );
	public function update( $_table, &$_array, $_where );
	public function getRowNum( $_query, $_res = false );
	public function count( $_table, $_field = 0, $_where = NULL );
}
?>