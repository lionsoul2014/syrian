<?php
/**
 * Common sql Model for syrian
 *      add database fetch interface
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-------------------------------------------------------
 
class SQLModel extends Model
{
 
    protected   $_mapping           = false;    //enable the mapping?
    protected   $_fields_mapping    = NULL;     //field name mapping
	protected	$_srw	= false;				//separate read/write

    //callback method quote
/*    protected   $_del_callback      = NULL;
    protected   $_add_callback      = NULL;
    protected   $_upt_callback      = NULL;*/
    
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
			else if ( $key[0] == '|' )
			{
				$key	= substr($key, 1);
				$_str 	.= " OR {$key} {$val}";
			}
			else if ( $key[0] == '&' )
			{
				$key	= substr($key, 1);
				$_str 	.= " AND {$key} {$val}";
			}
			else 
			{
				$_str 	.= " AND {$key} {$val}";
			}
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
	 * execute the specifield query string
	 *
	 * @param	String $_sql
	 * @param	$opt
	 * @param	$_row return the affected rows?
	 * @reruen	Mixed
	*/
	public function execute( $_sql, $opt=Idb::WRITE_OPT, $_row=false )
	{
		return $this->db->execute($_sql, $opt, $_row, $this->_srw);
	}

    /**
     * get the total count
     *
     * @param   $_where
	 * @param	$_group
    */
    public function totals( $_where = NULL, $_group = NULL )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
		if ( is_array( $_group ) ) $_group = implode(',', $_group);

        return $this->db->count($this->table, 0, $_where, $_group, $this->_srw);
    }

    /**
     * Get a vector from the specifiel table
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
	 * @param	$_group
    */
    public function getList( 
		$_fields,
		$_where = NULL, 
		$_order = NULL, 
		$_limit = NULL,
		$_group = NULL )
    {
        if ( is_array( $_fields) ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);

        $_sql  = 'select '.$_fields.' from ' . $this->table;
        if ( $_where != NULL ) $_sql .= ' where ' . $_where;
        if ( $_group != NULL ) $_sql .= ' group by ' . $_group;
        if ( $_order != NULL ) $_sql .= ' order by ' . $_order;
        if ( $_limit != NULL ) $_sql .= ' limit ' . $_limit;
        
        return $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
    }

	/**
	 * Quick way to fetch small sets from a big data sets
	 *	like do data pagenation.
	 * @Note: the primary key is very important for this function
	 *
	 * @param	$_fields		query fields array
	 * @param	$_where
	 * @param	$_order
	 * @param	$_limit
	 */
	public function fastList( 
		$_fields, 
		$_where = NULL, 
		$_order = NULL, 
		$_limit = NULL,
		$_group = NULL)
	{
        if ( is_array( $_fields) ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);

		//apply the where and the order and the limit 
		//	to search the primary key only
		//@Note: this is the key point of this method (cause it is fast)
		$_subquery	= 'select ' . $this->primary_key . ' from ' . $this->table;
        if ( $_where != NULL ) $_subquery .= ' where ' . $_where;
        if ( $_group != NULL ) $_subquery .= ' group by ' . $_group;
        if ( $_order != NULL ) $_subquery .= ' order by ' . $_order;
        if ( $_limit != NULL ) $_subquery .= ' limit ' . $_limit;

		//if the limit is NULL we can just take the subquery as the 
		//	value of the in condition, or we need to submit the subquery
		//(@Note: drop this way cause the in subquery is terrible for mysql)

		//and to the get the primary key token imploded with ','
		//@Note: fuck the unsupport of the limit in subquery of mysql
		$ret = $this->db->getList($_subquery, MYSQLI_ASSOC, $this->_srw);
		if ( $ret == false ) 
		{
			return false;
		}

		//implode the primary key with ','
		$idret		= array();
		foreach ( $ret as $val ) 
		{
			$idret[] = $val["{$this->primary_key}"];
		}

		$idstring	= implode(',', $idret);

		//make the main query and contains the sub query
		$_sql = "select {$_fields} from {$this->table} where {$this->primary_key} in({$idstring})";
        if ( $_order != NULL ) $_sql .= ' order by '. $_order;

        return $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
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
        
        return $this->db->getOneRow($_sql, MYSQLI_ASSOC, $this->_srw);
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
	 * @return	Mixed false or row_id
    */
    public function add( &$_data )
    {
        return $this->db->insert($this->table, $_data);
    }

	//batch add
	public function batchAdd( &$_data )
	{
		return $this->db->batchInsert($this->table, $_data);
	}

    /**
     * Increase the value of the specifield field of 
     *      the specifiled records in data table $this->table
     *
     * @param   $_field
	 * @param	$_offset
     * @param   $_where
	 * @return	bool
    */
    public function increase( $_field, $_offset, $_where )
    {
		$setting = NULL;
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
		if ( is_array($_field) )
		{
			$pair = array();
			foreach ( $_field as $key => $off )
			{
				$pair[] = "{$key}={$key}+{$off}";
			}

			$setting = implode(',', $pair);
		}
		else
		{
			$setting = "{$_field}={$_field}+{$_offset}";
		}

		//build the final sql
        $_sql = 'update ' . $this->table;
        $_sql .= " set {$setting} where ${_where}";
                
        //TODO: check and log the error as needed
        return $this->db->execute($_sql, Idb::WRITE_OPT, true, false);
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
		$setting = NULL;
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
		if ( is_array($_field) )
		{
			$pair = array();
			foreach ( $_field as $key => $off )
			{
				$pair[] = "{$key}={$key}-{$off}";
			}

			$setting = implode(',', $pair);
		}
		else
		{
			$setting = "{$_field}={$_field}-{$_offset}";
		}

        $_sql = 'update ' . $this->table;
        $_sql .= " set {$setting} where {$_where}";
                
        //TODO: check and log the error as needed
        return $this->db->execute($_sql, Idb::WRITE_OPT, true, false);
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

        return $this->db->execute( $_sql, Idb::WRITE_OPT, true, false );
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
    public static function getDatabase( $_key, $_db )
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

	/**
	 * set the debug status
	 *
	 * @param	$_debug
	 * @return	$this
	 */
	public function setDebug($_debug)
	{
		$this->db->setDebug($_debug);
		return $this;
	}

	/**
	 * start the read/write separate
	 *
	 * @return	$this
	 */
	public function startSepRaw()
	{
		$this->_srw = true;
		return $this;
	}

	/**
	 * close the read/write separate
	 *
	 * @return	$this
	 */
	public function closeSepRaw()
	{
		$this->_srw = false;
		return $this;
	}

	/**
	 * destruct method for the model
	 *	release the database connection
	*/
	public function __destruct()
	{
		if ( isset($this->db) && $this->db != NULL )
		{
			unset($this->db);
		}

		if ( isset($this->tables) && $this->tables != NULL )
		{
			unset($this->tables);
		}
	}
}
?>
