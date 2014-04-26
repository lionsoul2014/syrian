<?php
/**
 * Common Model for Sql database operation
 *      add database fetch interface
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-------------------------------------------------------
 
class SQLModel extends Model
{
 
    protected   $_mapping           = false;    //enable the mapping?
    protected   $_fields_mapping    = NULL;     //field name mapping
    
    public function __construct()
    {
        parent::__construct();

        //TODO:
        /*
        Add $this->table for the main table of the current model
        Add $this->primary_key for the main key of the table

        Set $this->_mapping
        Set $this->_fields_mapping
        */
    }

    /*
     * Set the current fields mapping status
     *  Enable the fields mapping by invoke setMapping(true)
     *
     * @param   $_mapping
    */
    protected function setMapping( $_mapping )
    {
        $this->_mapping = $_mapping;
    }

    /**
     * make the Array style condition to sql where condition string
     *      We may add a mapping here
     *
     * @param   $_where array('Id'=>'=1', 'pcate_id'=>'=2')
    */
    protected function getSqlWhere( &$_where )
    {
        $_str = NULL;
        foreach ($_where as $key => $val ) 
        {
            if ( $_str == NULL ) $_str = "{$key} {$val}";
            else $_str .= " and {$key} {$val}";
        }

        return $_str;
    }

    /**
     * Make the Array style fields to sql fields
     *  we may checking the mapping here
     * 
     * So, when the moment you start the field mapping
     *  don't forget to add the field alias ( AS ALIAS)
     *
     * @param   $_fields
    */
    protected function getSqlFields( &$_fields )
    {
        return implode(',', $_fields);
    }

    //get the sql style order
    protected function getSqlOrder( &$_order )
    {
        $str = NULL;
        foreach ($_order as $key => $val ) 
        {
            if ( $str == NULL ) $str = "{$key} {$val}";
            else $str .= ",{$key} {$val}";
        }

        //return implode(' ', $_order);
        return $str;
    }

    /**
     * get the total count
     *
     * @param   $_where
    */
    public function totals( $_where = NULL )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);

        return $this->db->count($this->table, 0, $_where);
    }

    /**
     * Get a vector from the specifiel table
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
    */
    public function getList( $_fields, $_where = NULL, $_order = NULL, $_limit = NULL )
    {
        if ( is_array( $_fields) ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);

        $_sql  = 'select '.$_fields.' from ' . $this->table;
        if ( $_where != NULL ) $_sql .= ' where ' . $_where;
        if ( $_order != NULL ) $_sql .= ' order by ' . $_order;
        if ( $_limit != NULL ) $_sql .= ' limit ' . $_limit;
        
        return $this->db->getList($_sql);
    }

    /**
     * get a specifiled record from the specifield table
     *
     * @param   $Id
     * @param   $_fields
    */
    public function get( $_fields, $_where )
    {
        if ( is_array( $_fields )  ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) )   $_where  = $this->getSqlWhere($_where);

        $_sql = 'select '.$_fields.' from ' .$this->table;
        $_sql .= ' where ' . $_where;
        
        return $this->db->getOneRow($_sql);
    }

    //get by primary key
    public function getById( $_fields, $id )
    {
        return $this->get($_fields, "{$this->primary_key}=$id");
    }

    //-------------------------------------------------------------------------

    /**
     * Add an new record to the database
     *
     * @param   $_data
    */
    public function add( &$_data )
    {
        return $this->db->insert($this->table, $_data);
    }

    /**
     * Increase the value of the specifield field of 
     *      the specifiled records in data table $this->table
     *
     * @param   $_field
     * @param   $_where
    */
    public function increase( $_field, $_offset, $_where )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);

        $_sql = 'update ' . $this->table;
        $_sql .= " set {$_field}={$_field}+{$_offset} where ${_where}";
                
        //TODO: check and log the error as needed
        return $this->db->execute($_sql);
    }

    //increase by primary_key
    public function increaseById( $_field, $_offset, $id )
    {
        return $this->increase( $_field, $_offset, "{$this->primary_key}={$id}" );
    }

    //----------------------------------------------------------------------------

    /**
     * reduce the value of the specifield field of the speicifled records
     *      in data table $this->table
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
    */
    public function reduce( $_field, $_offset, $_where )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);

        $_sql = 'update ' . $this->table;
        $_sql .= " set {$_field}={$_field}-{$_offset} where {$_where}";
                
        //TODO: check and log the error as needed
        return $this->db->execute($_sql);
    }

    //reduce by primary_key
    public function reduceById( $_field, $_offset, $id )
    {
        return $this->reduce($_field, $_offset, "{$this->primary_key}=$id");
    }

    //----------------------------------------------------------------------

    /**
     * Set the value of the specifield field of the speicifled reocords
     *      in data table $this->table
     *
     * @param   $_field
     * @param   $_val
     * @param   $_where
    */
    public function set( $_field, $_val, $_where  )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);

        $_sql  = 'update ' . $this->table;
        $_sql .= " set {$_field}='{$_val}' where ${_where}";

        return $this->db->execute( $_sql );
    }

    //set by primary key
    public function setById( $_field, $_val, $id )
    {
        return $this->set($_field, $_val, "{$this->primary_key}=$id");
    }

    //-----------------------------------------------------------------------

    //Conditioan update
    public function update( &$_data, $_where )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);

        return $this->db->update($this->table, $_data, $_where);
    }

    //update by primary key
    public function updateById( &$_data, $id )
    {
        return $this->update($_data, "{$this->primary_key}=$id");
    }

    //---------------------------------------------------------------------

    /**
     * Delete the specifield records
     *
     * @param   $_where
    */
    public function delete($_where)
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);

        return $this->db->delete($this->table, $_where);
    }

    //delete by primary key
    public function deleteById( $id )
    {
        return $this->delete("{$this->primary_key}=$id");
    }

    //------------------------------------------------------------------------
    
    /**
     * Quick interface to create the db instance
     *  It is a Object of class lib/db/Idb
     *
     * @see     ${BASEPATH}/lib/db/DbFactory
     *
     * @param   $_key (Mysql, Postgresql, Oracle, Mongo)
     * @return  Object
    */
    public function getDatabase( $_key, $_db )
    {
        //Load the database factory
        Loader::import('DbFactory', 'db');
        
        //Load the database config
        $_conf = Loader::config('hosts', 'db');
        
        if ( ! isset($_conf[$_db]) )
        {
            exit('Error: Invalid db section#' . $_db);
        }
        
        return DbFactory::create($_key, $_conf[$_db]);
    }
}
?>