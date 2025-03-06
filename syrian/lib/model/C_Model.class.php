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
    protected $fields = null;           //table fields setting mapping
    protected $separator = ',';         //array field default separator
    protected $_debug = false;
    protected $_onDuplicateKey = NULL;  //on duplicate key handler

    /**
     * Basic setting for the current model
     * If set autoPrimaryKey = true:
     * We will generate a uuid to replace the default primary key
    */
    protected $primary_key    = NULL;
    protected $autoPrimaryKey = false;

    /**
     * UID strategy
     * optional value: uint64, hex32str
    */
    protected   $uid_strategy = 'hex32str';


    /**
     * @Note: this is a core function added at 2015-06-13
     * with this you could sperate the fields of you table
     *     so store them in different section
    */
    protected $fragments  = NULL;
    protected $isFragment = false;
    protected $modelPool  = array();

    public function __construct()
    {
        //TODO:
        /*
         * Add $this->table for the main table of the current model
         * Add $this->primary_key for the main key of the table
         *
         * Set $this->fields
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
     * get the fields settting array
     *
     * @return  Mixed (Array or null)
    */
    public function getFields()
    {
        return $this->fields;
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
             * @Note: added at 2020/07/08
             * check and define the quote
            */
            $qstr = '\'';
            if (is_array($val)) {
                if (isset($val['quote']) && $val['quote'] == false) {
                    $qstr = NULL;
                }
                $val = isset($val['value']) ? $val['value'] : null;
            }

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
                    $val  = "={$qstr}{$nval}{$qstr}{$parenthesis}";
                }
                break;
            case '>':
            case '<':
                $hasEqual = $val[1] == '=' ? '=' : NULL;
                $sIdx = $hasEqual == NULL ? 1 : 2;
                if ( ! self::isStringQuoted($val, $sIdx, $eIdx) ) {
                    $nval = trim(substr($val, $sIdx, $eIdx - $sIdx + 1));
                    $val  = "{$val[0]}{$hasEqual}{$qstr}{$nval}{$qstr}{$parenthesis}";
                }
                break;
            case '!':   //!=
                if ( ! self::isStringQuoted($val, 2, $eIdx) ) {
                    $nval = trim(substr($val, 2, $eIdx - 1));   //$eIdx - 2 + 1
                    $val  = "!={$qstr}{$nval}{$qstr}{$parenthesis}";
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

                    $varr[$key] = "{$qstr}{$v_item}{$qstr}";
                }

                $nval = implode(',', $varr);
                $val  = "{$opt_key}($nval){$parenthesis}";
                break;
            case 'l':
                if (strncmp($val, "like ", 5) == 0) {
                    $nval = trim(substr($val, 5));
                    $val  = "like {$qstr}{$nval}{$qstr}{$parenthesis}";
                }
                break;
            default:
                if ( ! self::isStringQuoted($val, 0, $eIdx) ) {
                    $nval = trim($val);
                    $val  = "={$qstr}{$nval}{$qstr}{$parenthesis}";
                }
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
     * @param   $a_fields
     * @return  string
    */
    protected function getSqlFields($_fields, &$a_fields=null)
    {
        if ( $this->fields != null ) {
            $a_fields = array();
            foreach ( $_fields as $f ) {
                if ( ! isset($this->fields[$f]) ) {
                    continue;
                }

                $attr = $this->fields[$f];
                switch ( $attr['type'] ) {
                case 'array':
                    $a_fields[] = $f;
                    break;
                }
            }
        }

        return implode(',', $_fields);
    }

    //get the sql style order
    protected function getSqlOrder($_order)
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
     * execute the specified query string
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
     * @param   $_default
     * @return  int
    */
    public function totals( $_where = NULL, $_group = NULL, $_default=0 )
    {
        if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
        if ( is_array( $_group ) ) $_group = implode(',', $_group);

        return $this->db->count($this->table, 0, $_where, $_group, $this->_srw, $_default);
    }

    /**
     * Get a vector from the specified table
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
            if ( is_string($_fields) ) $_fields = explode(',', $_fields);
            $fieldsMap = array_flip($_fields);
            $sQueries = array();
            $this_cls = get_class($this);
            foreach ( $this->fragments as $fragment ) {
                $item = array();
                foreach ( $fragment['fields'] as $field ) {
                    if ( isset($fieldsMap[$field]) ) {
                        $item[$field] = true;
                        unset($fieldsMap[$field]);
                    }
                }

                if ( ! empty($item) ) {
                    /* Avoid recursive references */
                    $frag_model = $this->getModel($fragment['model']);
                    if (get_class($frag_model) != $this_cls) {
                        $sQueries[] = array(
                            'fields' => &$item,
                            'model'  => $frag_model
                        );
                    }
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

        $a_fields = null;
        if ( is_array( $_where ) ) $_where  = $this->getSqlWhere($_where);
        if ( is_array( $_group ) ) $_group  = implode(',', $_group);
        if ( is_array( $_order ) ) $_order  = $this->getSqlOrder($_order);
        if ( $_limit != NULL     ) $_limit  = $this->getSqlLimit($_limit);
        if ( is_array( $_fields) ) {
            $_fields = $this->getSqlFields($_fields, $a_fields);
        }

        $_sql = "select {$_fields} from {$this->table}";
        if ( $_where != NULL ) $_sql .= " where {$_where}";
        if ( $_group != NULL ) $_sql .= " group by {$_group}";
        if ( $_order != NULL ) $_sql .= " order by {$_order}";
        if ( $_limit != NULL ) $_sql .= " limit {$_limit}";

        $ret = $this->db->getList($_sql, MYSQLI_ASSOC, $this->_srw);
        if ( $ret == false ) return false;

        /*
         * @Note added at 2016/08/17
         * check and automatically convert the array fields to array
         * for array fields support of DMBS model
        */
        if ( $a_fields != null ) {
            foreach ( $ret as $key => $val ) {
                foreach ( $a_fields as $f ) {
                    $value = $val[$f];
                    if ( strlen($value) < 2 ) {
                        $val[$f] = array();
                    } else {
                        $val[$f] = explode($this->separator, $value);
                        array_shift($val[$f]);
                    }
                }

                $ret[$key] = $val;
            }
        }

        if ( ! $isFragment || empty($sQueries) ) {
            return $ret;
        }


        //--------------------------------
        //do and merge the fragment query results
        $idstr = self::implode($ret, $this->primary_key, ',');
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
            $subret = $sModel->getList($fields, array($priKey => "in({$idstr})"));
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
     * get a specified record from the specified table
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
            $this_cls = get_class($this);
            foreach ( $this->fragments as $fragment ) {
                $item = array();
                foreach ( $fragment['fields'] as $field ) {
                    if ( isset($fieldsMap[$field]) ) {
                        $item[$field] = true;
                        unset($fieldsMap[$field]);
                    }
                }

                if ( ! empty($item) ) {
                    /* Avoid recursive references */
                    $frag_model = $this->getModel($fragment['model']);
                    if (get_class($frag_model) != $this_cls) {
                        $sQueries[] = array(
                            'fields' => &$item,
                            'model'  => $frag_model
                        );
                    }
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

        $a_fields = null;
        if ( is_array($_where)  ) $_where = $this->getSqlWhere($_where);
        if ( is_array($_fields) ) {
            $_fields = $this->getSqlFields($_fields, $a_fields);
        }

        $sql = "select {$_fields} from {$this->table} where {$_where}";
        $ret = $this->db->getOneRow($sql, MYSQLI_ASSOC, $this->_srw);
        if ( $ret == false ) return false;

        /*
         * @Note added at 2016/08/17
         * @see  #getList
        */
        if ( $a_fields != null ) {
            foreach ( $a_fields as $f ) {
                $value = $ret[$f];
                if ( strlen($value) < 2 ) {
                    $ret[$f] = array();
                } else {
                    $ret[$f] = explode($this->separator, $value);
                    array_shift($ret[$f]);
                }
            }
        }

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
            array($this->primary_key => "='{$id}'")
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
         * @Note added at 2016/08/17
         * for array fields support
         * check and convert the array fields to string
        */
        if ( $this->fields != null ) {
            $this->stdDataType($data);
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
        $this_cls = get_class($this);
        foreach ( $this->fragments as $fragment ) {
            $item = array();
            foreach ( $fragment['fields'] as $field ) {
                //if ( isset($data[$field]) ) {
                if ( array_key_exists($field, $data) ) {
                    $item[$field] = &$data[$field];
                    unset($data[$field]);
                }
            }

            /*
             * @Note added at 2016/08/04
             * if the sync_w attribtues is marked as true that means
             * no matter there is data that is going to inserted to the fragment or not
             * we will do the insert operation for the current fragment
             *
             * @Note added at 2020/02/15
             * Add logic to check and Avoid recursive references
            */
            if ( ! empty($item) 
                || (isset($fragment['sync_w']) && $fragment['sync_w']) ) {
                $frag_model = $this->getModel($fragment['model']);
                if (get_class($frag_model) != $this_cls) {
                    $sData[] = array(
                        'data'  => &$item,
                        'model' => $frag_model
                    );
                }
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
        foreach ( $data as $key => $val ) {
            if ( $this->autoPrimaryKey == true 
                && ! isset($val[$this->primary_key]) ) {
                //generate the univeral unique identifier
                $val[$this->primary_key] = $this->genUUID($val);
            }

            if ( $this->fields != null ) $this->stdDataType($val);
            $data[$key] = $val;
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
            $sData = array();
            $this_cls = get_class($this);
            foreach ( $this->fragments as $fragment ) {
                $item = array();
                foreach ( $fragment['fields'] as $field ) {
                    //if ( isset($data[$field]) ) {
                    if ( array_key_exists($field, $data) ) {
                        $item[$field] = &$data[$field];
                        unset($data[$field]);
                    }
                }

                if ( ! empty($item) ) {
                    /* Avoid recursive references */
                    $frag_model = $this->getModel($fragment['model']);
                    if (get_class($frag_model) != $this_cls) {
                        $sData[] = array(
                            'data'  => &$item,
                            'model' => $frag_model
                        );
                    }
                }

                unset($item);
            }
        }

        # backup the original where condition
        $_where = $where;

        /*
         * @Note added at 2016/08/17
         * for array fields support
         * check and convert the array fields to string
        */
        if ( $this->fields != null ) {
            $this->stdDataType($data);
        }

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

            /*
             * @added at 2016/09/07
             * if there is nothing match this and we should just return false here
            */
            if ( $ret == false ) {
                return false;
            }

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
            array($this->primary_key => "='{$id}'"),
            $affected_rows
        );
    }

    /**
     * Set the value of the specified field of the specified reocords
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
            array($this->primary_key => "='{$id}'"),
            $affected_rows
        );
    }

    /** 
     * case set implementation .
     *
     * @param   $field  field to update
     * @param   $values value mapping with (case value : field value)
     * @param   $case_field field for case
     * @param   $_where where condition
     * @param   $affected_rows return the affected rows ?
     * @return  Mixed (False for failed)
    */
    public function setByCase(
        $field, $values, $case_field, $_where=null, $affected_rows=true)
    {
        $_sql   = array();
        $_sql[] = "UPDATE {$this->table} SET {$field}=(CASE {$case_field}";

        /* build the when then conditions */
        foreach ( $values as $val ) {
            $_sql[] = "WHEN {$val[0]} THEN '{$val[1]}'";
        }

        $_sql[] = 'END)';
        if ( $_where != null ) {
            $_sql[] = "WHERE";
            $_sql[] = is_array($_where) ? $this->getSqlWhere($_where) : $_where;
        }

        $_ret = $this->db->execute(implode(' ', $_sql), Idb::WRITE_OPT, false, false);
        if ( $_ret == FALSE ) {
            return false;
        }

        return $affected_rows ? $this->db->getAffectedRows() : true;
    }

    /**
     * Increase the value of the specified field of
     *      the specified records in data table $this->table
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
            array($this->primary_key => "='{$id}'")
        );
    }

    /**
     * decrease the value of the specified field of the specified records
     *  in data table $this->table
     *
     * @param   $_field
     * @param   $_offset
     * @param   $where
    */
    public function decrease( $_field, $_offset, $where )
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
    public function decreaseById( $_field, $_offset, $id )
    {
        return $this->decrease(
            $_field,
            $_offset,
            array($this->primary_key => "='{$id}'")
        );
    }

    /**
     * expand the value of specified array fields
     *
     * @param   $_field
     * @param   $val
     * @param   $where
     * @param   $flag
     * @param   bool
    */
    public function expand($_field, $val, $where, $flag=IModel::ADD_TAIL)
    {
        //stdlize the data
        $data = array();
        if ( is_array($_field) ) {
            foreach ( $_field as $key => $val ) {
                $lflag = $flag;
                if ( is_array($val) ) {
                    if ( isset($val['flag']) ) $lflag = $val['flag'];
                    $val = $val['value'];
                }

                $value = $lflag == IModel::ADD_HEAD 
                    ? "concat('{$this->separator}{$value}', $key)" 
                    : "concat({$key}, '{$this->separator}{$value}')";
                $data[$key] = array(
                    'value' => $value,
                    'quote' => false
                );
            }
        } else {
            $value = $flag == IModel::ADD_HEAD 
                ? "concat('{$this->separator}{$val}', $_field)" 
                : "concat({$_field}, '{$this->separator}{$val}')";
            $data[$_field]  = array(
                'value' => $value,
                'quote' => false
            );
        }

        //backup the original where condition
        $_where = $where;

        if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
        return $this->db->update($this->table, $data, $_where, false, true);
    }

    # expand by primary key
    public function expandById($_field, $val, $id, $flag=IModel::ADD_TAIL)
    {
        return $this->expand(
            $_field,
            $val,
            array($this->primary_key => "='{$id}'"),
            $flag
        );
    }

    /**
     * reduce the value of specified array fields
     *
     * @param   $_field
     * @param   $val
     * @param   $where
    */
    public function reduce($_field, $val, $where)
    {
        //stdlize the data
        $data = array();
        if ( is_array($_field) ) {
            foreach ( $_field as $key => $val ) {
                $data[$key] = array(
                    'value' => "replace({$key}, '{$this->separator}{$val}', '')",
                    'quote' => false
                );
            }
        } else {
            $data[$_field]  = array(
                'value' => "replace({$key}, '{$this->separator}{$val}', '')",
                'quote' => false
            );
        }

        //backup the original where condition
        $_where = $where;

        if ( is_array($_where) ) $_where = $this->getSqlWhere($_where);
        return $this->db->update($this->table, $data, $_where, false, true);
    }

    # reduce by primary key
    public function reduceById($_field, $val, $id)
    {
        return $this->reduce(
            $_field,
            $val,
            array($this->primary_key => "='{$id}'")
        );
    }

    /**
     * Delete the specified records
     *
     * @param   $_where
     * @param   $frag_recur
     * @param   $affected_rows
     * @fragments support
    */
    public function delete($where, $frag_recur=true, $affected_rows=true)
    {
        //backup the original where condition
        $_where = $where;

        //@Note: once fragments is define fragment delete must be executed
        //so this->isFragment == false checking disabled
        if ( $frag_recur == false || $this->fragments == NULL ) {
            if ( is_array( $_where ) ) $_where = $this->getSqlWhere($_where);
            return $this->db->delete($this->table, $_where, $affected_rows);
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
        $ret    = $this->db->delete($this->table, $_where, false);
        if ( $ret == false ) {
            return $affected_rows ? $this->db->getAffectedRows() : false;
        }

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
        $this_cls = get_class($this);
        $af_rows  = $affected_rows ? $this->db->getAffectedRows() : 0;
        $primary  = NULL;
        foreach ( $this->fragments as $fragment ) {
            /* Avoid recursive references */
            $sModel = $this->getModel($fragment['model']);
            if (get_class($sModel) == $this_cls) {
                continue;
            }

            $swhere = array($sModel->getPrimaryKey() => "{$idstr}");
            if ( $sModel->delete($swhere, false, false) == false ) {
                $activeModel = ($sModel instanceof C_Model) ? $sModel : $sModel->getLastActiveModel();
                throw new Exception("Fail to do the sub delete for model identified with " . $activeModel->getTableName());
            }
        }

        return $affected_rows ? $af_rows : true;
    }

    //delete by primary key
    //@frament suports
    public function deleteById( $id )
    {
        return $this->delete(
            array($this->primary_key => "='{$id}'")
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
     * Add a new fragment setting
     *
     * @param   $model_path
     * @param   $fields
     * @param   $sync_w
     * @return  $this
    */
    public function addFragment($model_path, $fields, $sync_w=false)
    {
        if ($this->fragments == NULL) {
            $this->fragments = array();
        }

        $this->fragments[] = array(
            'model'  => $model_path,
            'sync_w' => $sync_w,
            'fields' => $fields
        );

        return $this;
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
     * standardlize the data type according to the define
     * of the model fields quote by $this->fields
     *
     * @param   $data
    */
    protected function stdDataType(&$data)
    {
        foreach ( $this->fields as $key => $attr ) {
            if ( ! isset($data[$key]) ) {
                continue;
            }

            $value = &$data[$key];
            switch ( $attr['type'] ) {
            case 'array':
                if ( is_array($value) ) {
                    $value = $this->separator . implode($this->separator, $value);
                } else if ( strlen($value) > 0 
                    && $value != '0' ) {
                    $value = "{$this->separator}{$value}";
                } else {
                    $value = '0';
                }
                break;
            }
        }
    }

    /**
     * internal function to generate a universal unique identifier
     *
     * @param   $data
     * @param   $router
     * @return  Mixed
    */
    public function genUUID($data)
    {
        if ( $this->uid_strategy[0] == 'u' ) {  // uint64
            return $this->genUInt64UUID($data);
        } else {    // default to hex 32 string
            return $this->genHStr32UUID($data);
        }
    }

    /**
     * generate a universal unique identifier
     *
     * @param   $data   original data
     * @return  String
    */
    public function genHStr32UUID($data)
    {
        /*
         * 1, create a guid
         * check and append the node name to
         *  guarantee the basic server unique
        */
        $prefix = NULL;
        if ( defined('SR_NODE_NAME') ) {
            $prefix = substr(md5(SR_NODE_NAME), 0, 8);
        } else {
            $prefix = sprintf("%04x%04x", mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        }

        $tArr = explode(' ', microtime());
        $tsec = $tArr[1];
        $msec = $tArr[0];
        if ( ($sIdx = strpos($msec, '.')) !== false ) {
            $msec = substr($msec, $sIdx + 1);
        }

        return sprintf(
            "%08x%08x%0s%04x%04x",
            $tsec,
            $msec,
            $prefix,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * generate a 8-bytes int unique identifier
     *
     * @param   $data   original data
     * @return  String
    */
    private static $uint64_seed = 0;
    public function genUInt64UUID($data)
    {
        // version 1: +-4Bytes-+-2Bytes-+-2Byte
        // version 2: +-4Bytes-+-3Bytes-+-1Byte
        // version 3: +-4Bytes-+-2Bytes-+-1byte+-1Byte
        // timestamp + microtime + Node name + static increase

        $uuid = 0x00;
        $tArr = explode(' ', microtime());
        $tsec = $tArr[1];
        $msec = $tArr[0];
        if ( ($sIdx = strpos($msec, '.')) !== false ) {
            $msec = substr($msec, $sIdx + 1);
        }

        $msec  = ($msec & 0x00FFFFFF);  // keep 3 bytes
        $uuid  = ($tsec << 32);         // timestamp
        $uuid |= ($msec << 8);          // microtime
        if ( defined('SR_NODE_NAME') ) {
            $nstr  = substr(md5(SR_NODE_NAME), 0, 2);
            $uuid |= hexdec($nstr) & 0xFF;    // node name serial no
        } else {
            $uuid |= mt_rand(0, 0xFF);        // ramdom node serial
        }

        return $uuid;
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

    //implode the array->fields with a specified glue
    public static function implode($arr, $field, $glue)
    {
        $idret = array();
        foreach ( $arr as $val ) {
            $idret[] = $val[$field];
        }

        return implode($glue, $idret);
    }

    //mapping arr->field with arr
    public static function makeIndex($arr, $field)
    {
        $mapping = array();
        foreach ( $arr as $val ) {
            $key = $val[$field];
            $mapping[$key] = $val;
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
        if ( $str === false || strlen($str) < 1 ) {
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
