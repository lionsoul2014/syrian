<?php
/**
 * Common Model for Openapi
 *      add database fetch interface
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-------------------------------------------------------
 
class SQLModel extends Model
{
    /**
     * the global read server selector factor
     * @Note: all the models will share the some read server
     *    for a single http request handler so make it static here
     * and only generate for once for each request
     *
     * @access    private
    */
    private static $_slaveFactor    = array();
 
    protected   $_mapping           = false;    //enable the mapping?
    protected   $_fields_mapping    = NULL;     //field name mapping
    protected    $_srw    = false;                //separate read/write
    protected    $_onDuplicateKey    = NULL;        //on duplicate key handler

    /**
     * @Note: this is a core function added at 2015-06-13
     * with this you could sperate the fields of you table 
     *     so store them in diffent section
    */
    protected    $fragments            = NULL;
    protected    $isFragment            = false;

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
                $key    = substr($key, 1);
                $_str     .= " OR {$key} {$val}";
            }
            else if ( $key[0] == '&' )
            {
                $key    = substr($key, 1);
                $_str     .= " AND {$key} {$val}";
            }
            else 
            {
                $_str     .= " AND {$key} {$val}";
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
        $ret    = array();
        foreach ($_order as $key => $val ) 
        {
            $ret[]    = "{$key} {$val}";
        }

        return implode(',', $ret);
    }

    /**
     * execute the specifield query string
     *
     * @param    String $_sql
     * @param    $opt
     * @param    $_row return the affected rows?
     * @reruen    Mixed
    */
    public function execute( $_sql, $opt=Idb::WRITE_OPT, $_row=false )
    {
        return $this->db->execute($_sql, $opt, $_row, $this->_srw);
    }

    /**
     * get the total count
     *
     * @param   $_where
     * @param    $_group
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
     * @param    $_group
     * @fragment supports
    */
    public function getList( 
        $_fields,
        $_where = NULL, 
        $_order = NULL, 
        $_limit = NULL,
        $_group = NULL )
    {
        $isFragment    = ($this->isFragment && $this->fragments != NULL);
        if ( $isFragment )
        {
            //pre-process the fields
            if ( is_string($_fields) )
            {
                $_fields= explode(',', $_fields);
            }
            $fieldsMap    = array_flip($_fields);

            //intercept the fragment fields
            $sQueries    = array();
            foreach ( $this->fragments as $fragment )
            {
                $item    = array();
                foreach ( $fragment['fields'] as $field )
                {
                    if ( isset($fieldsMap[$field]) )
                    {
                        $item[$field] = true;
                        unset($fieldsMap[$field]);
                    }
                }

                if ( ! empty($item) )
                {
                    $sQueries[] = array(
                        'fields'        => &$item,
                        'primary_key'    => $fragment['primary_key'],
                        'table'            => $fragment['table']
                    );
                }

                unset($item);
            }

            if ( ! empty($sQueries) )
            {
                //check and append the primary key
                if ( ! isset($fieldsMap[$this->primary_key]) )
                {
                    $fieldsMap[$this->primary_key] = true;
                }

                $_fields = array_keys($fieldsMap);
            }
        }

        if ( is_array( $_fields) ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);

        $_sql     = 'select '.$_fields.' from ' . $this->table;
        if ( $_where != NULL ) $_sql .= ' where ' . $_where;
        if ( $_group != NULL ) $_sql .= ' group by ' . $_group;
        if ( $_order != NULL ) $_sql .= ' order by ' . $_order;
        if ( $_limit != NULL ) $_sql .= ' limit ' . $_limit;

        $ret    = $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
        if ( $ret == false ) return false;
        if ( ! $isFragment || empty($sQueries) )
        {
            return $ret;
        }


        //--------------------------------
        //do and merge the fragment query results
        $idstring    = self::implode($ret, $this->primary_key, ',');
        foreach ( $sQueries as $Query )
        {
            $fields    = $Query['fields'];
            $remove    = false;
            if ( ! isset($fields[$Query['primary_key']]) )
            {
                $remove = true;
                $fields[$Query['primary_key']] = true;
            }

            $fields    = array_keys($fields);
            $fields    = $this->getSqlFields($fields);
            $_sql    = "select {$fields} from {$Query['table']} where {$Query['primary_key']} in({$idstring});";
            $subret    = $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
            if ( $subret == false ) 
            {
                continue;
            }

            $sIndex    = self::makeIndex($subret, $Query['primary_key']);
            unset($subret, $fields);

            //merge the sub query
            $data    = array();
            foreach ( $ret as $val )
            {
                $primary    = $val[$this->primary_key];
                if ( ! isset($sIndex["{$primary}"]) )
                {
                    continue;
                }

                $subval    = $sIndex["{$primary}"];
                if ( $remove ) unset($subval[$Query['primary_key']]);
                $data[]    = array_merge($val, $subval);
            }

            $ret    = &$data;
            unset($data);
        }

        return $ret;
    }

    /**
     * Quick way to fetch small sets from a big data sets
     *    like do data pagenation.
     * @Note: the primary key is very important for this function
     *
     * @param    $_fields        query fields array
     * @param    $_where
     * @param    $_order
     * @param    $_limit
     * @fragment supports
     */
    public function fastList( 
        $_fields, 
        $_where = NULL, 
        $_order = NULL, 
        $_limit = NULL,
        $_group = NULL)
    {
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);

        //apply the where and the order and the limit 
        //    to search the primary key only
        //@Note: this is the key point of this method (cause it is fast)
        $_subquery    = 'select ' . $this->primary_key . ' from ' . $this->table;
        if ( $_where != NULL ) $_subquery .= ' where ' . $_where;
        if ( $_group != NULL ) $_subquery .= ' group by ' . $_group;
        if ( $_order != NULL ) $_subquery .= ' order by ' . $_order;
        if ( $_limit != NULL ) $_subquery .= ' limit ' . $_limit;

        //if the limit is NULL we can just take the subquery as the 
        //    value of the in condition, or we need to submit the subquery
        //(@Note: drop this way cause the in subquery is terrible for mysql)

        //and to the get the primary key token imploded with ','
        //@Note: fuck the unsupport of the limit in subquery of mysql
        $ret = $this->db->getList($_subquery, MYSQLI_ASSOC, $this->_srw);
        if ( $ret == false ) 
        {
            return false;
        }

        //implode the primary key with ','
        $idstr    = self::implode($ret, $this->primary_key, ',');

        //make the main query and contains the sub query
        if ( $this->isFragment == false || $this->fragments == NULL )
        {
            if ( is_array( $_fields) ) $_fields = $this->getSqlFields($_fields);

            $_sql = "select {$_fields} from {$this->table} where {$this->primary_key} in({$idstr})";
            if ( $_order != NULL ) $_sql .= ' order by '. $_order;

            return $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
        }

        //invoke the getList to offer the fragment support
        return $this->getList($_fields, "{$this->primary_key} in({$idstr})", $_order, NULL, NULL);
    }

    /**
     * get a specifiled record from the specifield table
     *
     * @param   $Id
     * @param   $_fields
     * @fragment supports
    */
    public function get( $_fields, $_where )
    {
        //check and intercept the fragments fields
        $isFragment    = ($this->isFragment && $this->fragments != NULL);
        if ( $isFragment )
        {
            //pre-process the fields
            if ( is_string($_fields) )
            {
                $_fields= explode(',', $_fields);
            }
            $fieldsMap    = array_flip($_fields);

            //intercept the fragment fields
            $sQueries    = array();
            foreach ( $this->fragments as $fragment )
            {
                $item    = array();
                foreach ( $fragment['fields'] as $field )
                {
                    if ( isset($fieldsMap[$field]) )
                    {
                        $item[$field] = true;
                        unset($fieldsMap[$field]);
                    }
                }

                if ( ! empty($item) )
                {
                    $sQueries[] = array(
                        'fields'        => &$item,
                        'primary_key'    => $fragment['primary_key'],
                        'table'            => $fragment['table']
                    );
                }

                unset($item);
            }

            if ( ! empty($sQueries) )
            {
                //check and append the primary key
                if ( ! isset($fieldsMap[$this->primary_key]) )
                {
                    $fieldsMap[$this->primary_key] = true;
                }

                $_fields = array_keys($fieldsMap);
            }
        }

        if ( is_array( $_fields )  ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) )   $_where  = $this->getSqlWhere($_where);

        $sql = 'select '.$_fields.' from ' . $this->table . ' where ' . $_where;
        $ret = $this->db->getOneRow($sql, MYSQLI_ASSOC, $this->_srw);
        if ( $ret == false ) return false;
        if ( ! $isFragment || empty($sQueries) )
        {
            return $ret;
        }


        //--------------------------------
        //merge the fragment query
        foreach ( $sQueries as $Query )
        {
            $fields    = $Query['fields'];
            $remove    = false;
            if ( ! isset($fields[$Query['primary_key']]) )
            {
                $remove = true;
                $fields[$Query['primary_key']] = true;
            }

            $fields    = array_keys($fields);
            $fields    = $this->getSqlFields($fields);
            $_sql    = "select {$fields} from {$Query['table']} where {$Query['primary_key']}={$ret[$this->primary_key]}";
            $subret    = $this->db->getOneRow($_sql, MYSQLI_ASSOC, $this->_srw);
            if ( $subret == false ) 
            {
                continue;
            }

            //merge the sub query
            if ( $remove ) unset($subret[$Query['primary_key']]);
            $ret = array_merge($ret, $subret);
        }

        return $ret;
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
     * @param   $data
     * @pparam    $onDuplicateKey
     * @return    Mixed false or row_id
     * @fragment support
    */
    public function add( &$data, $onDuplicateKey=NULL )
    {
        $onDK    = $onDuplicateKey ? $onDuplicateKey : $this->_onDuplicateKey;
        if ( $this->isFragment == false || $this->fragments == NULL ) {
            return $this->db->insert($this->table, $data, $onDK);
        }

        //-----------------------------------
        //intercept the fragments data
        $sData    = array();
        foreach ( $this->fragments as $fragment )
        {
            $item    = array();
            foreach ( $fragment['fields'] as $field )
            {
                if ( isset($data[$field]) )
                {
                    $item[$field] = &$data[$field];
                    unset($data[$field]);
                }
            }

            if ( ! empty($item) )
            {
                $sData[] = array(
                    'data'            => &$item,
                    'primary_key'    => $fragment['primary_key'],
                    'table'            => $fragment['table']
                );
            }

            unset($item);
        }

        //1. insert the basic info
        $insertedId = $this->db->insert($this->table, $data, $onDK);
        if ( $insertedId == false ) return false;
        if ( empty($sData) ) return true;

        //2. process the fragments data insertion
        foreach ( $sData as $fragment )
        {
            $fragment['data'][$fragment['primary_key']] = $insertedId;
            if ( $this->db->insert($fragment['table'], $fragment['data'], NULL, true) == false )
            {
                return -1;
            }
        }
        
        return $insertedId;
    }

    /**
     * batch add with no fragments support
    */
    public function batchAdd( &$_data, $onDuplicateKey=NULL )
    {
        $onDK    = $onDuplicateKey ? $onDuplicateKey : $this->_onDuplicateKey;
        return $this->db->batchInsert($this->table, $_data, $onDK);
    }

    /**
     * Conditioan update
     *
     * @param    $data
     * @param    $_where
     * @return    Mixed
    */
    public function update( &$data, $_where )
    {
        $isFragment    = $this->isFragment && $this->fragments != NULL;
        if ( $isFragment )
        {
            //intercept the fragments data
            $sData    = array();
            foreach ( $this->fragments as $fragment )
            {
                $item    = array();
                foreach ( $fragment['fields'] as $field )
                {
                    if ( isset($data[$field]) )
                    {
                        $item[$field] = &$data[$field];
                        unset($data[$field]);
                    }
                }

                if ( ! empty($item) )
                {
                    $sData[] = array(
                        'data'            => &$item,
                        'primary_key'    => $fragment['primary_key'],
                        'table'            => $fragment['table']
                    );
                }

                unset($item);
            }
        }

        //check and intercept the fragments execute
        //    for not fragment or empty interceptions
        if ( $isFragment == false || empty($sData) )
        {
            if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
            return $this->db->update($this->table, $data, $_where);
        }


        //--------------------------------------
        //prepare the update condition
        //@Note:
        //there is another efficient way to do this job, like:
        //1. the following sql query:
        // SET @idstring := '';
        // UPDATE leray_stream SET is_rec = 0 WHERE is_rec = 1 AND ( SELECT @idstring := CONCAT_WS(',', Id, @idstring) );
        // SELECT @idstring as idstring; 
        //
        // but it only return the affected rows and does not mean we don't have to do
        //     the fragment table updates when the main table update affected no rows
        $idstr    = NULL;
        if ( is_array($_where) && count($_where) == 1 && isset($_where[$this->primary_key]) )
        {
            $idstr    = $_where[$this->primary_key];
        }
        else
        {
            if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
            $_sql    = "select {$this->primary_key} from {$this->table} where {$_where}";
            $ret    = $this->db->getList($_sql, MYSQLI_ASSOC, false);
            $idstr    = 'in(' . self::implode($ret, $this->primary_key, ',') . ')';
        }

        $ret = $this->db->update($this->table, $data, "{$this->primary_key} {$idstr}", true, false);
        //@Note: set the 5th arguments to 5 for to return the sql execute results only
        //    cuz by default it will return the affected rows
        if ( $ret == false ) return false;
        $r    = $this->db->getAffectedRows();

        //process the fragments data updates
        $primary    = NULL;
        foreach ( $sData as $fragment )
        {
            $primary = $fragment['primary_key'];
            $r = $r || $this->db->update($fragment['table'], $fragment['data'], "{$primary} {$idstr}");
        }
        
        return $r;
    }

    //update by primary key
    public function updateById( &$_data, $id )
    {
        return $this->update($_data, array($this->primary_key => "={$id}"));
    }

    /**
     * Set the value of the specifield field of the speicifled reocords
     *      in data table $this->table
     *
     * @param   $_field
     * @param   $_val
     * @param   $_where
     * @fragment support
    */
    public function set( $_field, $_val, $_where  )
    {
        //@Note: at 2015-04-08 00:44
        //    for normal sql will cuz sql error for string slashed
        //$_sql  = 'update ' . $this->table;
        //$_sql .= " set {$_field}='{$_val}' where ${_where}";
        //return $this->db->execute( $_sql, Idb::WRITE_OPT, true, false );

        $data    = array($_field => $_val);
        return $this->update($data, $_where);
    }

    //set by primary key
    //@fragments support
    public function setById( $_field, $_val, $id )
    {
        return $this->set($_field, $_val, array($this->primary_key => "={$id}"));
    }

    /**
     * Increase the value of the specifield field of 
     *      the specifiled records in data table $this->table
     *
     * @param   $_field
     * @param    $_offset
     * @param   $_where
     * @return    bool
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

    /**
     * Delete the specifield records
     *
     * @param   $_where
     * @fragments suport
    */
    public function delete($_where)
    {
        //@Note: once fragments is define
        //    fragment delete must be executed
        if ( /*$this->isFragment == false ||*/ $this->fragments == NULL )
        {
            if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
            return $this->db->delete($this->table, $_where);
        }

        //-------------------------------------
        //prepare the delete codition
        //@Note: check the #update for may coming solution
        $idstr    = NULL;
        if ( is_array($_where) && count($_where) == 1 && isset($_where[$this->primary_key]) )
        {
            $idstr    = $_where[$this->primary_key];
        }
        else
        {
            if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
            $_sql    = "select {$this->primary_key} from {$this->table} where {$_where}";
            $ret    = $this->db->getList($_sql, MYSQLI_ASSOC, false);
            $idstr    = '(' . self::implode($ret, $this->primary_key, ',') . ')';
        }

        $ret = $this->db->delete($this->table, "{$this->primary_key} {$idstr}");
        if ( $ret == false ) return false;

        //process the fragments data delete
        $primary    = NULL;
        foreach ( $this->fragments as $frament )
        {
            $primary = $frament['primary_key'];
            if ( $this->db->delete($frament['table'], "{$primary} {$idstr}") == false )
            {
                return -1;
            }
        }

        return true;
    }

    //delete by primary key
    //@frament suports
    public function deleteById( $id )
    {
        return $this->delete(array($this->primary_key => "={$id}"));
    }

    /**
     * set the handler for on duplicate key
     *
     * @param    $handler
    */
    public function onDuplicateKey($handler)
    {
        $this->_onDuplicateKey = $handler;
        return $this;
    }

    /**
     * active the fragment status
     *
     * @return    $this
    */
    public function openFragment()
    {
        $this->isFragment = true;
        return $this;
    }

    /**
     * disactive the fragment status
     *
     * @return $this
    */
    public function closeFragment()
    {
        $this->isFragment = false;
        return $this;
    }

    /**
     * set the debug status
     *
     * @param    $_debug
     * @return    $this
     */
    public function setDebug($_debug)
    {
        $this->db->setDebug($_debug);
        return $this;
    }

    /**
     * start the read/write separate
     *
     * @return    $this
     */
    public function startSepRaw()
    {
        $this->_srw = true;
        return $this;
    }

    /**
     * close the read/write separate
     *
     * @return    $this
     */
    public function closeSepRaw()
    {
        $this->_srw = false;
        return $this;
    }

    /**
     * get the last inserted id
     *
     * @return    false || Integer
    */
    public function getLastInsertId()
    {
        return $this->db->getLastInsertId();
    }


    /**
     * get the last error
     *
     * @return    string    
    */
    public function getLastError()
    {
        return $this->db->getLastError();
    }

    /**
     * destruct method for the model
     *    release the database connection
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

    //---------------------------------------------
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
        $conf  = Loader::config('hosts', 'db', false, $_db );
        if ( $conf == false )
        {
            exit('Error: Invalid db section#' . $_db);    
        }

        $serial    = $conf['serial'];

        //check the read/write separate and factor generate
        //@Note: cuz syrian is singleton database instance
        //so, for each database instance, we just need to invoke slaveStrategy for once
        if ( ! isset(self::$_slaveFactor[$serial])
            && isset($conf['__r']) && count($conf['__r']) > 1 )
        {
            $seed    = time();
            self::$_slaveFactor[$serial] = $seed;
            return DbFactory::create($_key, $conf)->slaveStrategy($seed);
        }
        
        return DbFactory::create($_key, $conf);
    }

    //implode the array->fields with a specifiled glue
    public static function implode(&$arr, $field, $glue)
    {
        $idret    = array();
        foreach ( $arr as $val )
        {
            $idret[] = $val[$field];
        }

        return implode($glue, $idret);
    }

    //mapping arr->field with arr
    public static function makeIndex(&$arr, $field)
    {
        $mapping    = array();
        foreach ( $arr as $val )
        {
            $key    = $val[$field];
            $mapping["{$key}"] = &$val;
            unset($val);
        }

        return $mapping;
    }
}
?>
