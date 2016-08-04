<?php
/**
 * model common interface
 *
 * Normal implementation    : C_Model           -Done
 * Sharding implementation  : ShardingModel     -Done
 * Memecached implementation: MemcachedModel
 * Mongo implementation     : MongoModel
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

//-----------------------------------------

interface IModel
{
    const ADD_OPT       = 0;
    const DELETE_OPT    = 1;
    const QUERY_OPT     = 2;
    const UPDATE_OPT    = 3;

    /**
     * get the last active C_Model object
     *
     * @return  C_Model
    */
    public function getLastActiveModel();

    /**
     * execute the specifield query command
     *
     * @param   $_query
     * @param   $opt code
     * @param   $_row return the affected rows?
     * @return  Mixed
    */
    public function execute($_query, $opt=0, $_row=false);

    /**
     * get the total count
     *
     * @param   $_where
     * @param   $_group
     * @return  int
    */
    public function totals($_where=NULL, $_group=NULL);

    /**
     * Get a vector from the specifiel source
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
     * @param   $_group
     * @fragment supports
    */
    public function getList($_fields, $_where=NULL, $_order=NULL, $_limit=NULL, $_group=NULL);

    /**
     * Quick way to fetch small sets from a big data sets
     *    like do data pagenation.
     * @Note: the primary key is very important for this function
     *
     * @param    $_fields   query fields array
     * @param    $_where
     * @param    $_order
     * @param    $_limit
     * @fragment supports
     */
    public function fastList($_fields, $_where=NULL, $_order=NULL, $_limit=NULL, $_group=NULL);

    /**
     * get a specifiled record from the specifield table
     *
     * @param   $Id
     * @param   $_fields
     * @fragment supports
    */
    public function get($_fields, $_where);

    //get by primary key
    public function getById($_fields, $id);

    //-------------------------------------------------------------------------

    /**
     * Add an new record to the data source
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Mixed false or row_id
     * @fragment support
    */
    public function add($data, $onDuplicateKey=NULL);

    /**
     * batch add with no fragments support
     *
     * @param   $data
    */
    public function batchAdd($data, $onDuplicateKey=NULL);

    /**
     * Conditioan update
     *
     * @param   $data
     * @param   $_where
     * @param   $affected_rows
     * @return  Mixed
    */
    public function update($data, $_where, $affected_rows=true);

    //update by primary key
    public function updateById($data, $id, $affected_rows=true);

    /**
     * Set the value of the specifield field of the speicifled reocords
     *  in data source
     *
     * @param   $_field
     * @param   $_val
     * @param   $_where
     * @param   $affected_rows
     * @fragment support
    */
    public function set($_field, $_val, $_where, $affected_rows=true);

    //set by primary key
    //@fragments support
    public function setById($_field, $_val, $id, $affected_rows=true);

    /**
     * Increase the value of the specifield field of 
     *  the specifiled records in data source
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
     * @return  bool
    */
    public function increase($_field, $_offset, $_where);

    //increase by primary_key
    public function increaseById($_field, $_offset, $id);

    /**
     * reduce the value of the specifield field of the speicifled records
     *  in data source
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
     * @return  Mixed
    */
    public function reduce($_field, $_offset, $_where);

    //reduce by primary_key
    public function reduceById($_field, $_offset, $id);

    /**
     * Delete the specifield records
     *
     * @param   $_where
     * @param   $frag_recur
     * @fragments suport
    */
    public function delete($_where, $frag_recur=true);

    //delete by primary key
    //@frament suports
    public function deleteById($id);

    /**
     * set the handler for on duplicate key
     *
     * @param   $handler
    */
    public function onDuplicateKey($handler);

    /**
     * set the debug status
     *
     * @param   $_debug
     * @return  $this
     */
    public function setDebug($debug);

    /**
     * start the read/write separate
     *
     * @return  $this
     */
    public function startSepRaw();

    /**
     * close the read/write separate
     *
     * @return  $this
     */
    public function closeSepRaw();

    /**
     * active the fragment status
     *
     * @return  $this
    */
    public function openFragment();

    /**
     * disactive the fragment status
     *
     * @return $this
    */
    public function closeFragment();

}
?>
