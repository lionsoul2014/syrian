<?php
/**
 * Common DBMS Model with common IDb interface implemented
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

import('model.IModel');

class C_Model implements IModel
{
    /**
     * the global read server selector factor
     * @Note: all the models will share the some read server
     *    for a single http request handler so make it static here
     * and only generate for once for each request
     *
     * @access    private
    */
    private static $_slaveFactor = array();

    protected $db   = NULL;
    protected $_srw = false;            //separate read/write
    protected $_mapping = false;        //enable the mapping?
    protected $_fields_mapping = NULL;  //field name mapping
    protected $_onDuplicateKey = NULL;  //on duplicate key handler

    /**
     * Basic setting for the current model
     * If set autoPrimaryKey = true:
     * We will generate a uuid to replace the default primary key
    */
    protected $primary_key    = NULL;
    protected $autoPrimaryKey = false;

    /**
     * @Note: this is a core function added at 2015-06-13
     * with this you could sperate the fields of you table
     *     so store them in different section
    */
    protected $fragments  = NULL;
    protected $isFragment = false;
    protected $modelPool  = array();

    protected $_debug = false;

    //callback method quote
/*    protected   $_del_callback    = NULL;
    protected   $_add_callback      = NULL;
    protected   $_upt_callback      = NULL;*/

    public function __construct()
    {
        //TODO:
        /*
         * Add $this->table for the main table of the current model
         * Add $this->primary_key for the main key of the table
         *
         * Set $this->_mapping
         * Set $this->_fields_mapping
        */

    }

    /**
     * @Note added at 2016/08/04
     * better way to manager the fragments models with:
     * 1, unique model instance to avoid the other affected
     * 2, with auto attribtues setting
     *
     * @param   $model_path
     * @return  Object Model Object
    */
    protected function getModel($model_path)
    {
        if ( isset($this->modelPool[$model_path]) ) {
            $model = $this->modelPool[$model_path];
        } else {
            $model = model($model_path, false);
            $this->modelPool[$model_path] = $model;
        }

        //check and set the onDuplicateKey handler
        //if ( $this->_onDuplicateKey !== NULL ) {
        //    $model->onDuplicateKey($this->_onDuplicateKey);
        //}

        //check and set the debug statue
        $model->setDebug($this->_debug);

        //check and set the read/write separate status
        if ( $this->_srw ) $model->startSepRaw();
        else $model->closeSepRaw();

        //check and set the fragment status
        //if ( $this->isFragment ) $model->openFragment();
        //else $model->closeFragment();

        return $model;
    }

    /**
     * return the internal db instance
     *
     * @param   Object
    */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * return the table name associated with the model
     *
     * @return  Mixed string or NULL
    */
    public function getTableName()
    {
        return isset($this->table) ? $this->table : NULL;
    }

    /**
     * return the primary key of the sql table
     *
     * @return  Mixed string or NULL
    */
    public function getPrimaryKey()
    {
        return isset($this->primary_key) ? $this->primary_key : NULL;
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
        $where = array();
        $delimiter = NULL;
        foreach ( $_where as $field => $val ) {
            /*
             * For more flexiable where condition syntax
             * We quote the value with single quotes by default
             *
             * @Added 2016-02-01
            */
            $val    = trim($val);
            $len    = strlen($val);
            $parenthesis = $val[$len-1] == ')' ? ')' : NULL;
            $eIdx   = $parenthesis == NULL ? $len - 1 : $len - 2;
            $opcode = strtolower($val[0]);

            switch ( $opcode ) {
            case '=':
                if ( ! self::isStringQuoted($val, 1, $eIdx) ) {
                    $nval = trim(substr($val, 1, $eIdx));
                    $val  = "='{$nval}'{$parenthesis}";
                }
                break;
            case '>':
            case '<':
                $hasEqual = $val[1] == '=' ? '=' : NULL;
                $sIdx = $hasEqual == NULL ? 1 : 2;
                if ( ! self::isStringQuoted($val, $sIdx, $eIdx) ) {
                    $nval = trim(substr($val, $sIdx, $eIdx - $sIdx + 1));
                    $val  = "{$val[0]}{$hasEqual}'{$nval}'{$parenthesis}";
                }
                break;
            case '!':   //!=
                if ( ! self::isStringQuoted($val, 2, $eIdx) ) {
                    $nval = trim(substr($val, 2, $eIdx - 1));   //$eIdx - 2 + 1
                    $val  = "!='{$nval}'{$parenthesis}";
                }
                break;
            case 'i':   //in(v1,v2)
            case 'n':
                $opt_key = NULL;
                if ( $opcode == 'i' ) {
                    if ( strtolower($val[1]) != 'n' ) break;
                    $opt_key = 'IN';
                } else if ( $opcode == 'n' ) {
                    if ( strtolower($val[1]) != 'o' ) break;
                    $opt_key = 'NOT IN';
                }

                //parenthesis re-define
                for ( $i = $len - 2; $i > 0; $i++ ) {
                    if ( $val[$i] == ' ' ) continue;
                    $parenthesis = $val[$i] == ')' ? ')' : NULL;
                    break;
                }

                $psIdx = strpos($val, '(');
                if ( $psIdx === false ) break;
                $psIdx++;
                $peIdx = strrpos($val, ')', $parenthesis==NULL ? -1 : -2);
                if ( $peIdx === false ) break;
                $inval = substr($val, $psIdx, $peIdx - $psIdx);
                $varr  = explode(',', $inval);

                foreach ( $varr as $key => $v_item ) {
                    $v_item = trim($v_item);
                    if ( self::isStringQuoted($v_item) ) {
                        continue;
                    }

                    $varr[$key] = "'{$v_item}'";
                }

                $nval = implode(',', $varr);
                $val  = "{$opt_key}($nval){$parenthesis}";
                break;
            }

            switch ( $field[0] ) {
            case '|':
                $field = substr($field, 1);
                $where[] = "OR {$field} {$val}";
                break;
            case '&':
                $field = substr($field, 1);
                $where[] = "AND {$field} {$val}";
                break;
            default:
                $where[] = "{$delimiter}{$field} {$val}";
                $delimiter = 'AND ';
                break;
            }
        }

        return implode(' ', $where);
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
        $ret = array();
        foreach ($_order as $key => $val ) {
            $ret[]  = "{$key} {$val}";
        }

        return implode(',', $ret);
    }

    /**
     * get the sql limit
     *
     * @param   $_limit
     * @return  string
    */
    protected function getSqlLimit($_limit)
    {
        $from = 0;
        $size = 0;
        if ( is_long($_limit) ) {
            $size = $_limit;
        } else if ( is_string($_limit) ) {
            $parts = explode(',', $_limit);
            if ( count($parts) == 1 ) {
                $size = intval($parts[0]);
            } else {
                $from = intval($parts[0]);
                $size = intval($parts[1]);
            }
        } else if ( is_array($_limit) ) {
            if ( count($_limit) == 1 ) {
                $size = $_limit[0];
            } else {
                $from = $_limit[0];
                $size = $_limit[1];
            }
        }

        return "{$from},{$size}";
    }

    /**
     * get the last active C_Model object
     *
     * @return  C_Model
    */
    public function getLastActiveModel()
    {
        return $this;
    }

    /**
     * execute the specifield query string
     *
     * @param   $_sql
     * @param   $opt
     * @param   $_row return the affected rows?
     * @return  Mixed
    */
    public function execute( $_sql, $opt=Idb::WRITE_OPT, $_row=false )
    {
        return $this->db->execute($_sql, $opt, $_row, $this->_srw);
    }

    /**
     * get the total count
     *
     * @param   $_where
     * @param   $_group
     * @return  int
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
     * @param   $_group
     * @fragment supports
    */
    public function getList(
        $_fields,
        $_where = NULL,
        $_order = NULL,
        $_limit = NULL,
        $_group = NULL )
    {
        $isFragment = ($this->isFragment && $this->fragments != NULL);
        if ( $isFragment ) {
            //pre-process the fields
            if ( is_string($_fields) ) {
                $_fields = explode(',', $_fields);
            }
            $fieldsMap = array_flip($_fields);

            //intercept the fragment fields
            $sQueries = array();
            foreach ( $this->fragments as $fragment ) {
                $item = array();
                foreach ( $fragment['fields'] as $field ) {
                    if ( isset($fieldsMap[$field]) ) {
                        $item[$field] = true;
                        unset($fieldsMap[$field]);
                    }
                }

                if ( ! empty($item) ) {
                    $sQueries[] = array(
                        'fields' => &$item,
                        'model'  => $this->getModel($fragment['model'])
                    );
                }

                unset($item);
            }

            if ( ! empty($sQueries) ) {
                //check and append the primary key
                if ( ! isset($fieldsMap[$this->primary_key]) ) {
                    $fieldsMap[$this->primary_key] = true;
                }

                $_fields = array_keys($fieldsMap);
            }
        }

        if ( is_array( $_fields) ) $_fields = $this->getSqlFields($_fields);
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( $_limit != NULL     ) $_limit  = $this->getSqlLimit($_limit);

        $_sql = 'select ' . $_fields . ' from ' . $this->table;
        if ( $_where != NULL ) $_sql .= ' where ' . $_where;
        if ( $_group != NULL ) $_sql .= ' group by ' . $_group;
        if ( $_order != NULL ) $_sql .= ' order by ' . $_order;
        if ( $_limit != NULL ) $_sql .= ' limit ' . $_limit;

        $ret = $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
        if ( $ret == false ) return false;
        if ( ! $isFragment || empty($sQueries) ) {
            return $ret;
        }


        //--------------------------------
        //do and merge the fragment query results
        $idstring = self::implode($ret, $this->primary_key, ',');
        foreach ( $sQueries as $Query ) {
            $fields = $Query['fields'];
            $sModel = $Query['model'];
            $priKey = $sModel->getPrimaryKey();
            $remove = false;
            if ( ! isset($fields[$priKey]) ) {
                $remove = true;
                $fields[$priKey] = true;
            }

            $fields = array_keys($fields);
            $subret = $sModel->getList($fields, array($priKey => "in({$idstring})"));
            if ( $subret == false ) {
                continue;
            }

            $sIndex = self::makeIndex($subret, $priKey);
            unset($subret, $fields);

            //merge the sub query
            $data = array();
            foreach ( $ret as $val ) {
                $primary = $val[$this->primary_key];
                if ( ! isset($sIndex["{$primary}"]) ) {
                    continue;
                }

                $subval = $sIndex["{$primary}"];
                if ( $remove ) unset($subval[$priKey]);
                $data[] = array_merge($val, $subval);
            }

            $ret = &$data;
            unset($data);
        }

        return $ret;
    }

    /**
     * Quick way to fetch small sets from a big data sets like do data pagenation.
     * @Note: the primary key is very important for this function
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
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
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( $_limit != NULL     ) $_limit  = $this->getSqlLimit($_limit);

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
        if ( $ret == false ) {
            return false;
        }

        //implode the primary key with ','
        $idstr  = self::implode($ret, $this->primary_key, ',');

        //make the main query and contains the sub query
        if ( $this->isFragment == false || $this->fragments == NULL ) {
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
        $isFragment = ($this->isFragment && $this->fragments != NULL);
        if ( $isFragment ) {
            //pre-process the fields
            if ( is_string($_fields) ) {
                $_fields= explode(',', $_fields);
            }
            $fieldsMap = array_flip($_fields);

            //intercept the fragment fields
            $sQueries = array();
            foreach ( $this->fragments as $fragment ) {
                $item = array();
                foreach ( $fragment['fields'] as $field ) {
                    if ( isset($fieldsMap[$field]) ) {
                        $item[$field] = true;
                        unset($fieldsMap[$field]);
                    }
                }

                if ( ! empty($item) ) {
                    $sQueries[] = array(
                        'fields' => &$item,
                        'model'  => $this->getModel($fragment['model'])
                    );
                }

                unset($item);
            }

            if ( ! empty($sQueries) ) {
                //check and append the primary key
                if ( ! isset($fieldsMap[$this->primary_key]) ) {
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
        if ( ! $isFragment || empty($sQueries) ) {
            return $ret;
        }


        //--------------------------------
        //merge the fragment query
        foreach ( $sQueries as $Query ) {
            $fields = $Query['fields'];
            $sModel = $Query['model'];
            $priKey = $sModel->getPrimaryKey();
            $remove = false;
            if ( ! isset($fields[$priKey]) ) {
                $remove = true;
                $fields[$priKey] = true;
            }

            /*
             * do not use getById here to instead of the primary_key searching
             * cuz the sharding model without guidKey did not working with it
             *
             * @Note: added at 2016-04-11
            */
            $fields = array_keys($fields);
            $subret = $sModel->get($fields, array($priKey => "={$ret[$this->primary_key]}"));
            if ( $subret == false ) {
                continue;
            }

            //merge the sub query
            if ( $remove ) {
                unset($subret[$priKey]);
            }

            $ret = array_merge($ret, $subret);
        }

        return $ret;
    }

    //get by primary key
    public function getById( $_fields, $id )
    {
        return $this->get(
            $_fields,
            array($this->primary_key => "={$id}")
        );
    }

    //-------------------------------------------------------------------------

    /**
     * Add an new record to the database
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Mixed false or row_id
     * @fragment support
    */
    public function add($data, $onDuplicateKey=NULL)
    {
        /*
         * check and append the auto generated primary_key
         * @Note : added at 2016/02/02
        */
        if ( $this->autoPrimaryKey == true
            && ! isset($data[$this->primary_key]) ) {
            $data[$this->primary_key] = $this->genUUID($data);
        }

        /*
         * return the affected rows for the insert operation ?
         * if the primary_key is set and we will return the affected rows
         * cuz at this situation, the default last inserted id will always
         * be null, so by default false will be returned always
        */
        $affected_rows = isset($data[$this->primary_key]) ? true : false;

        $onDK = $onDuplicateKey ? $onDuplicateKey : $this->_onDuplicateKey;
        if ( $this->isFragment == false || $this->fragments == NULL ) {
            $r = $this->db->insert($this->table, $data, $onDK, $affected_rows);
            if ( $r != false ) {
                return isset($data[$this->primary_key]) ? $data[$this->primary_key] : $r;
            }

            return false;
        }

        //-----------------------------------
        //intercept the fragments data
        $sData = array();
        foreach ( $this->fragments as $fragment ) {
            $item = array();
            foreach ( $fragment['fields'] as $field ) {
                if ( isset($data[$field]) ) {
                    $item[$field] = &$data[$field];
                    unset($data[$field]);
                }
            }

            /*
             * @Note added at 2016/08/04
             * if the sync_w attribtues is marked as true that means
             * no matter there is data that is going to inserted to the fragment no not
             * we will do the insert operation for the current fragment
            */
            if ( ! empty($item) 
                || (isset($fragment['sync_w']) && $fragment['sync_w']) ) {
                $sData[] = array(
                    'data'  => &$item,
                    'model' => $this->getModel($fragment['model'])
                );
            }

            unset($item);
        }

        //1. insert the basic info
        $insertedId = $this->db->insert($this->table, $data, $onDK, $affected_rows);
        if ( $affected_rows ) {
            $insertedId = $data[$this->primary_key];
        } else {
            /*
             * Old version(None auto primary key) support
             * auto append the last inserted id as the primary_key
            */
            $data[$this->primary_key] = $insertedId;
        }

        if ( $insertedId == false ) return false;
        if ( ! empty($sData) ) {
            //2. process the fragments data insertion
            foreach ( $sData as $Query ) {
                $sModel = $Query['model'];
                $Query['data'][$sModel->getPrimaryKey()] = $insertedId;
                if ( $sModel->add($Query['data']) == false ) {
                    $activeModel = ($sModel instanceof C_Model) ? $sModel : $sModel->getLastActiveModel();
                    throw new Exception("Fail to do the sub add for model identified with " . $activeModel->getTableName());
                }
            }
        }

        if ( $insertedId != false ) {
            return isset($data[$this->primary_key]) ? $data[$this->primary_key] : $insertedId;
        }

        return false;
    }

    /**
     * batch add with no fragments support
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Integer affected rows
    */
    public function batchAdd($data, $onDuplicateKey=NULL)
    {
        /*
         * check and append the auto generate primary_key value
         * @Note: added at 2016.02.02
        */
        if ( $this->autoPrimaryKey == true ) {
            foreach ( $data as $key => $val ) {
                if ( isset($val[$this->primary_key]) ) {
                    continue;
                }

                //generate the univeral unique identifier
                $val[$this->primary_key] = $this->genUUID($val);
                $data[$key] = $val;
            }
        }

        $onDK = $onDuplicateKey ? $onDuplicateKey : $this->_onDuplicateKey;
        return $this->db->batchInsert($this->table, $data, $onDK);
    }

    /**
     * Conditioan update
     *
     * @param   $data
     * @param   $where
     * @param   $affected_rows
     * @return  Mixed
     * @fragment support
    */
    public function update($data, $where, $affected_rows=true)
    {
        $isFragment = $this->isFragment && $this->fragments != NULL;
        if ( $isFragment ) {
            //intercept the fragments data
            $sData  = array();
            foreach ( $this->fragments as $fragment ) {
                $item   = array();
                foreach ( $fragment['fields'] as $field ) {
                    if ( isset($data[$field]) ) {
                        $item[$field] = &$data[$field];
                        unset($data[$field]);
                    }
                }

                if ( ! empty($item) ) {
                    $sData[] = array(
                        'data'  => &$item,
                        'model' => $this->getModel($fragment['model'])
                    );
                }

                unset($item);
            }
        }

        //backup the original where condition
        $_where = $where;

        //check and intercept the fragments execute
        //    for not fragment or empty interceptions
        if ( $isFragment == false || empty($sData) ) {
            if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
            return $this->db->update($this->table, $data, $_where, true, $affected_rows);
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
        $idstr  = NULL;
        if ( is_array($_where) && count($_where) == 1 && isset($_where[$this->primary_key]) ) {
            $idstr = $_where[$this->primary_key];
        } else {
            if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
            $_sql  = "select {$this->primary_key} from {$this->table} where {$_where}";
            $ret   = $this->db->getList($_sql, MYSQLI_ASSOC, false);
            $idstr = 'in(' . self::implode($ret, $this->primary_key, ',') . ')';
        }

        if ( empty($data) ) {
            $r = $affected_rows ? false : true;
        } else {
            $_where = "{$this->primary_key} {$idstr}";
            $ret = $this->db->update($this->table, $data, $_where, true, false);
            //@Note: set the 5th arguments to 5 for to return the sql execute results only
            //    cuz by default it will return the affected rows
            if ( $ret == false ) return false;
            $r = $this->db->getAffectedRows();
        }

        //process the fragments data updates
        foreach ( $sData as $Query ) {
            $m = $Query['model'];
            $d = $Query['data'];
            $w = array($m->getPrimaryKey() => "{$idstr}");
            $r = $m->update($d, $w, $affected_rows) || $r;
        }

        return $r;
    }

    //update by primary key
    public function updateById($data, $id, $affected_rows=true)
    {
        return $this->update(
            $data,
            array($this->primary_key => "={$id}"),
            $affected_rows
        );
    }

    /**
     * Set the value of the specifield field of the speicifled reocords
     *      in data table $this->table
     *
     * @param   $_field
     * @param   $_val
     * @param   $_where
     * @param   $affected_rows
     * @fragment support
    */
    public function set($_field, $_val, $_where, $affected_rows=true)
    {
        //@Note: at 2015-04-08 00:44
        //    for normal sql will cuz sql error for string slashed
        //$_sql  = 'update ' . $this->table;
        //$_sql .= " set {$_field}='{$_val}' where ${_where}";
        //return $this->db->execute( $_sql, Idb::WRITE_OPT, true, false );

        $data = array($_field => $_val);
        return $this->update($data, $_where, $affected_rows);
    }

    //set by primary key
    //@fragments support
    public function setById($_field, $_val, $id, $affected_rows=true)
    {
        //return $this->set(
        //    $_field,
        //    $_val,
        //    array($this->primary_key => "={$id}"),
        //    $affected_rows
        //);

        $data = array($_field => $_val);
        return $this->update(
            $data,
            array($this->primary_key => "={$id}"),
            $affected_rows
        );
    }

    /**
     * Increase the value of the specifield field of
     *      the specifiled records in data table $this->table
     *
     * @param   $_field
     * @param   $_offset
     * @param   $where
     * @return  bool
    */
    public function increase( $_field, $_offset, $where )
    {
        //stdlize the data
        $data = array();
        if ( is_array($_field) ) {
            foreach ( $_field as $key => $off ) {
                $data[$key] = array(
                    'value' => "{$key}+{$off}",
                    'quote' => false
                );
            }
        } else {
            $data[$_field]  = array(
                'value' => "{$_field}+{$_offset}",
                'quote' => false
            );
        }

        //backup the original where condition
        $_where = $where;

        if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
        return $this->db->update($this->table, $data, $_where, false, true);
    }

    //increase by primary_key
    public function increaseById( $_field, $_offset, $id )
    {
        return $this->increase(
            $_field,
            $_offset,
            array($this->primary_key => "={$id}")
        );
    }

    /**
     * reduce the value of the specifield field of the speicifled records
     *  in data table $this->table
     *
     * @param   $_field
     * @param   $_offset
     * @param   $where
    */
    public function reduce( $_field, $_offset, $where )
    {
        $data = array();
        if ( is_array($_field) ) {
            foreach ( $_field as $key => $off ) {
                $data[$key] = array(
                    'value' => "{$key}-{$off}",
                    'quote' => false
                );
            }
        } else {
            $data[$_field]  = array(
                'value' => "{$_field}-{$_offset}",
                'quote' => false
            );
        }

        //backup the original where condition
        $_where = $where;

        if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
        return $this->db->update($this->table, $data, $_where, false, true);
    }

    //reduce by primary_key
    public function reduceById( $_field, $_offset, $id )
    {
        return $this->reduce(
            $_field,
            $_offset,
            array($this->primary_key => "={$id}")
        );
    }

    /**
     * Delete the specifield records
     *
     * @param   $_where
     * @param   $frag_recur
     * @fragments support
    */
    public function delete($where, $frag_recur=true)
    {
        //backup the original where condition
        $_where = $where;

        //@Note: once fragments is define fragment delete must be executed
        //so this->isFragment == false checking disabled
        if ( $frag_recur == false || $this->fragments == NULL ) {
            if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
            return $this->db->delete($this->table, $_where);
        }

        //-------------------------------------
        //prepare the delete codition
        //@Note: check the #update for may coming solution
        $idstr  = NULL;
        if ( is_array($_where) && count($_where) == 1 && isset($_where[$this->primary_key]) ) {
            $idstr = $_where[$this->primary_key];
        } else {
            if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
            $_sql  = "select {$this->primary_key} from {$this->table} where {$_where}";
            $ret   = $this->db->getList($_sql, MYSQLI_ASSOC, false);
            $idstr = '(' . self::implode($ret, $this->primary_key, ',') . ')';
        }

        $_where = "{$this->primary_key} {$idstr}";
        $ret    = $this->db->delete($this->table, $_where);
        if ( $ret == false ) return false;

        /*
         * process the fragments data delete
         *
         * @Note: here we got a invoke recursive problem
         * for the sub model has set the current model as the fragment
         * Is good that all the fragments will be delete even without a complete fragments setting
         * and that will cuz double invoke of delete for each fragment model
         *
         * So, the frag_recur arguments is going to solve this problem
         * sub query never do the fragment query
        */

        $primary = NULL;
        foreach ( $this->fragments as $fragment ) {
            $sModel = $this->getModel($fragment['model']);
            $swhere = array($sModel->getPrimaryKey() => "{$idstr}");
            if ( $sModel->delete($swhere, false) == false ) {
                $activeModel = ($sModel instanceof C_Model) ? $sModel : $sModel->getLastActiveModel();
                throw new Exception("Fail to do the sub delete for model identified with " . $activeModel->getTableName());
            }
        }

        return true;
    }

    //delete by primary key
    //@frament suports
    public function deleteById( $id )
    {
        return $this->delete(
            array($this->primary_key => "={$id}")
        );
    }

    /**
     * set the handler for on duplicate key
     *
     * @param   $handler
    */
    public function onDuplicateKey($handler)
    {
        $this->_onDuplicateKey = $handler;
        return $this;
    }

    /**
     * set the debug status
     *
     * @param   $_debug
     * @return  $this
     */
    public function setDebug($_debug)
    {
        $this->_debug = $_debug;
        $this->db->setDebug($_debug);
        return $this;
    }

    /**
     * start the read/write separate
     *
     * @return  $this
     */
    public function startSepRaw()
    {
        $this->_srw = true;
        return $this;
    }

    /**
     * close the read/write separate
     *
     * @return  $this
     */
    public function closeSepRaw()
    {
        $this->_srw = false;
        return $this;
    }

    /**
     * get the last inserted id
     *
     * @return  false || Integer
    */
    public function getLastInsertId()
    {
        return $this->db->getLastInsertId();
    }


    /**
     * get the last error
     *
     * @return  string
    */
    public function getLastError()
    {
        return $this->db->getLastError();
    }

    /**
     * get the last error code
     *
     * @return  int
    */
    public function getLastErrno()
    {
        return $this->db->getLastErrno();
    }

    //------------For fragment function-----------------

    /**
     * return the fragments info for the current model
     *
     * @return  Mixed Array or NULL
    */
    public function getFragmentInfo()
    {
        return isset($this->fragments) ? $this->fragments : NULL;
    }

    /**
     * active the fragment status
     *
     * @return  $this
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
     * generate a universal unique identifier
     *
     * @param   $data   original data
     * @return  String
    */
    protected function genUUID($data)
    {
        /*
         * 1, create a guid
         * check and append the node name to
         *  guarantee the basic server unique
        */
        $prefix = NULL;
        if ( defined('SR_NODE_NAME') ) {
            $prefix = substr(md5(SR_NODE_NAME), 0, 4);
        } else {
            $prefix = sprintf("%04x", mt_rand(0, 0xffff));
        }

        $tArr = explode(' ', microtime());
        $tsec = $tArr[1];
        $msec = $tArr[0];
        if ( ($sIdx = strpos($msec, '.')) !== false ) {
            $msec = substr($msec, $sIdx + 1);
        }

        return sprintf(
            "%08x%08x%0s%04x",
            $tsec,
            $msec,
            $prefix,
            mt_rand(0, 0xffff)
        );
    }


    /**
     * destruct method for the model
     *  release the database connection
    */
    public function __destruct()
    {
        if ( isset($this->db) && $this->db != NULL ) {
            unset($this->db);
        }

        if ( isset($this->tables) && $this->tables != NULL ) {
            unset($this->tables);
        }
    }



    //-------------Static tool function------------------
    
    /**
     * Quick interface to create the db instance
     *  It is a Object of class lib/db/Idb
     *
     * @see     ${BASEPATH}/lib/db/DbFactory
     *
     * @param   $key (Mysql, Postgresql, Oracle, Mongo)
     * @param   $db
     * @return  Object
    */
    public static function getDatabase( $key, $db )
    {
        //Load the database factory
        import('db.DbFactory');

        //Load the database config
        $conf = config("database#{$db}");
        if ( $conf == false ) {
            throw new Exception("Error: Invalid db section {$db}");
        }

        $serial = $conf['serial'];

        //check the read/write separate and factor generate
        //@Note: cuz syrian is singleton database instance
        //so, for each database instance, we just need to invoke slaveStrategy for once
        if ( ! isset(self::$_slaveFactor[$serial])
            && isset($conf['__r']) && count($conf['__r']) > 1 ) {
            $seed = time();
            self::$_slaveFactor[$serial] = $seed;
            return DbFactory::create($key, $conf)->slaveStrategy($seed);
        }

        return DbFactory::create($key, $conf);
    }

    //implode the array->fields with a specifiled glue
    public static function implode(&$arr, $field, $glue)
    {
        $idret = array();
        foreach ( $arr as $val ) {
            $idret[] = $val[$field];
        }

        return implode($glue, $idret);
    }

    //mapping arr->field with arr
    public static function makeIndex(&$arr, $field)
    {
        $mapping = array();
        foreach ( $arr as $val ) {
            $key = $val[$field];
            $mapping["{$key}"] = &$val;
            unset($val);
        }

        return $mapping;
    }

    /**
     * check if the string is quoted by single quotes or double quotes
     *
     * @param   $str
     * @param   $sIdx
     * @param   $eIdx
     * @return  boolean
    */
    private static function isStringQuoted($str, $sIdx=0, $eIdx=-1)
    {
        if ( $str == false || strlen($str) < 1 ) {
            return true;
        }

        if ( $eIdx == -1 ) {
            $eIdx = strlen($str) - 1;
        }

        return (
            ($str[$sIdx] == '\'' && $str[$eIdx] == '\'')
            || ($str[$sIdx] == '"' && $str[$eIdx] == '"')
        );
    }

}
?>
