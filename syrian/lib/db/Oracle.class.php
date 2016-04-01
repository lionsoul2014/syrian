<?php
class Oracle implements Idb
{
    public function execute( $_sql, $opt, $_row = false, $src = NULL ) {}
    public function insert( $_table, &$_array, $onDuplicateKey=NULL ) {}
    public function batchInsert( $_table, &$_array, $onDuplicateKey=NULL, $affected_rows=false ) {}
    public function delete( $_table, $_where, $affected_rows=true ) {}
    public function getList( $_query, $_type = NULL, $srw = NULL ) {}
    public function getOneRow( $_query, $_type = NULL, $srw = NULL ) {}
    public function update( $_table, &$_array, $_where, $slashes=true, $affected_rows=true ) {}
    public function getRowNum( $_query, $_res = false, $srw = NULL ) {}
    public function count( $_table, $_field, $_where = NULL, $_group = NULL, $srw = NULL ) {}
    public function setDebug( $_debug ) {}
    public function setSepRW( $srw ) {}
    public function slaveStrategy( $factor ) {}
    public function getLastInsertId() {}
    public function getAffectedRows() {}
    public function getLastError() {}
    public function getLastErrno() {}
    public function release() {}
    public function getSerial() {}
}
?>
