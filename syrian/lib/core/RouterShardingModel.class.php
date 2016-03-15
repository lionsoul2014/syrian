<?php
/**
 * Child version of C_Model with basic sharding support
 *
 * @started 2016-01-25
 * @See     C_Model
 * @author  chenxin<chenxin619315@gmail.com>
*/

Loader::import('IModel', 'core', true);

//--------------------------------------------

class RouterShardingModel implements IModel 
{
    /**
     * @Note: this is a core function started at 2016-01-25
     * with this you could separate coming data flow into different tables
     *  router maybe specifiled to guide the storage
    */
    protected   $shardings  = NULL;

    /**
     * sharding router:
     * 1, once being set and it can not be changed
     * cuz we will merge the router value into the global unique id
     * 2, If not specifiled, you should set the global id as the default router
     *
     * @see #genUUID($data)
    */
    protected   $router     = NULL;

    /**
     * the global uinque identifier
     * guid will be generated and append to the data that is 
     * going to be inserted into by Specifield this
     *
     * We suggest you do this
    */
    protected   $guidKey    = NULL;

    /**
     * control attributes
    */
    protected   $_onDuplicateKey = NULL;
    protected   $_debug     = NULL;
    protected   $_srw       = NULL;
    protected   $isFragment = NULL;
    protected   $isView     = NULL;

    /**
     * last active C_Model object
    */
    protected   $lastAcitveModel = NULL;


    public function __construct()
    {
        /*
         * set the following option
         *
         * $this->shardings
         * $this->guidKey
         * $this->router
        */

    }


    /**
     * get the last active C_Model object
     *
     * @return  C_Model
    */
    public function getLastActiveModel()
    {
        return $this->lastAcitveModel;
    }

    /**
     * execute the specifield query string
     *
     * @param   String $_sql
     * @param   $opt
     * @param   $_row return the affected rows?
     * @return  Mixed
    */
    public function execute( $_sql, $opt=Idb::WRITE_OPT, $_row=false )
    {
        return false;
    }

    /**
     * get the total count
     *
     * @param   $_where
     * @param   $_group
     * @return  int
    */
    public function totals($_where=NULL, $_group=NULL )
    {
        $count = 0;
        $shardingModels = $this->__getQueryShardingModels($_where);

        /*
         * count all the sub query result as the final total count
         * With a router is the best of course.
        */
        foreach ( $shardingModels as $sharding ) {
            $pModel = $sharding['model'];
            $count += $pModel->totals($sharding['where'], $_group);
        }

        return $count;
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
        $_group = NULL)
    {
        $shardingModels = $this->__getQueryShardingModels($_where);

        /*
         * if there is only one sharding model returned
         * we could simple return the query result of that target model
         * And you should always keep it working like this way.
         * 
         * or dispatch and merge work got be done
        */
        if ( count($shardingModels) == 1 ) {
            $pModel = $shardingModels[0]['model'];
            return $pModel->getList($_fields, $shardingModels[0]['where'], $_order, $_limit, $_group);
        }

        /*
         * If the fields is not a global fields query: 
         * check and append the sorting field
         * cuz we have to make sure the sorting field is in the field list
         * or append it in, mark it, then remove it from the result set later.
        */
        $keysToRemove    = array();
        $isValidateOrder = ($_order != NULL && is_array($_order) && count($_order) > 0);
        if ( $_fields[0] != '*' && $isValidateOrder == true ) {
            $fieldsMap = array_flip($_fields);
            foreach ( $_order as $key => $val ) {
                if (isset($fieldsMap[$key])) {
                    continue;
                }

                $_fields[] = $key;  //append the sorting keys
                $fieldsMap[$key]    = true;
                $keysToRemove[$key] = $key;
            }
        }

        /*
         * lets do the query dispatch, merge the sub result
         * Liked the map-reduce way ... 
        */
        $ret = array();
        foreach ( $shardingModels as $sharding ) {
            $pModel = $sharding['model'];
            $subret = $pModel->getList($_fields, $sharding['where'], $_order, $_limit, $_group);
            if ( $subret == false ) continue;
            $ret = array_merge($ret, $subret);
        }

        /*
         * check and do the sorting
         * Sort all the demnsion like the db sorting do
        */
        if ( $isValidateOrder == true ) {
            $param  = array();
            foreach ($_order as $order_key => $order_way) {
                //build the dimension data array
                $dimension = array();
                foreach ( $ret as $key => $val ) {
                    $dimension[$key] = $val[$order_key];
                }

                $sort_way = stripos($order_way, 'desc') !== false ? SORT_DESC : SORT_ASC;
                $sort_typ = SORT_REGULAR;

                //append the arguments
                $param[]  = $dimension;
                $param[]  = $sort_way;
                $param[]  = $sort_typ;
            }

            $param[] = $ret;
            call_user_func_array('array_multisort', $param);

            //check and do all the sorting key dimension remove
            if ( ! empty($keysToRemove) ) {
                foreach ( $ret as $key => $val ) {
                    foreach ( $keysToRemove as $rm_key ) {
                        unset($val[$rm_key]);
                    }
                    $ret[$key] = $val;
                }
            }
        }

        /*
         * check and do the query limit
         * Oh, this may sounds crazy if a vector query without a limit!!!
        */
        if ( $_limit != NULL ) {
            $lArr = explode(',', $_limit);
            $dLen = intval(count($lArr) == 1 ? $lArr[0] : $lArr[1]);
            if ( $dLen < count($ret) ) {
                $ret = array_slice($ret, 0, $dLen);
            }
        }

        return empty($ret) ? false : $ret;
    }

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
    public function fastList( 
        $_fields, 
        $_where = NULL, 
        $_order = NULL, 
        $_limit = NULL,
        $_group = NULL)
    {
        return $this->getList($_fields, $_where, $_order, $_limit, $_group);
    }

    /**
     * get a specifiled record from the specifield table
     *
     * @param   $_fields
     * @param   $_where
     * @fragment supports
    */
    public function get($_fields, $_where)
    {
        /*
         * router checking
         * This will run a little bit faster than checking it in the 
         * '__getQueryShardingModels' sharding define function
        */
        if ( ! isset($_where[$this->router]) ) {
            $this->routerError(true);
        }

        $shardingModels = $this->__getQueryShardingModels($_where);
        if ( count($shardingModels) > 1 ) {
            return false;
        }

        $pModel = $shardingModels[0]['model'];
        return $pModel->get($_fields, $shardingModels[0]['where']);
    }

    /**
     * get by primary key
     * And the router must needed to execute this query
     *
     * @param   $_fields
     * @param   $id
     * @return  Mixed
     * @Deprecated
    */
    public function getById($_fields, $id)
    {
        /*
         * Only through the global unique identifier 
         * could this query be executed.
         * @Note: guid generated by #genUUID(Array)
        */
        $pModel = $this->getShardingModelFromId($id);
        if ( $pModel == false ) {
            return false;
        }

        /*
         * Now, do the query dispatch and directly
         * return the executed result
        */
        return $pModel->getById($_fields, $id);
    }

    //---------------------------------------------------------------

    /**
     * Add an new record to the database
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Mixed false or row_id
     * @fragment support
    */
    public function add(&$data, $onDuplicateKey=NULL)
    {
        /*
         * According to the design, we got to generate a unique global id 
         * for every record. 
         * As Mysql, you could replace the default value of the primary_key Id.
         * Tow basic request for the global id:
         * 1, global unique
         * 2, must contains the router info insite
         * or all the xxById interface will not work properly
        */
        if ( $this->guidKey != NULL ) {
            $data[$this->guidKey] = self::genUUID($data, $this->router);
        }

        /*
         * check the router and the router must need
         * At lease you could take the uuid as the router
        */
        if ( ! isset($data[$this->router]) ) {
            $this->routerError(true);
        }

        //sharding checking
        $pModel = $this->__getWriteShardingModel($data);
        if ( $pModel == false ) {
            $this->routerError(true);
        }

        return $pModel->add($data, $onDuplicateKey);
    }

    /**
     * batch add with no fragments support
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Integer affected rows
    */
    public function batchAdd(&$data, $onDuplicateKey=NULL)
    {
        /*
         * group the data
         * By the final sharding idx defined throught the router value 
        */
        $groupData = $this->groupDataBySharding($data, true);
        $gDataSet  = $groupData[0];
        $modelSet  = $groupData[1];

        // insert dispatch
        $pModel = NULL;
        $affected_rows = 0;
        foreach ( $gDataSet as $shardIdx => $val ) {
            $pModel = $modelSet[$shardIdx];
            $afRows = $pModel->batchAdd($val, $onDuplicateKey);
            if ( $afRows == false ) {
                continue;
            }

            $affected_rows += $afRows;
        }

        //reset the last active model
        $this->lastAcitveModel = $pModel;

        return $affected_rows;
    }

    /**
     * Conditioan update
     *
     * @param   $data
     * @param   $_where
     * @param   $affected_rows
     * @return  Mixed
     * @see     #getList(...)
    */
    public function update(&$data, $_where, $affected_rows=true)
    {
        $shardingModels = $this->__getQueryShardingModels($_where);

        /*
         * check and quick handler the optimized shot
         * or dispatch and merge work got be done
        */
        if ( count($shardingModels) == 1 ) {
            $pModel = $shardingModels[0]['model'];
            return $pModel->update($data, $shardingModels[0]['where'], $affected_rows);
        }

        /*
         * Now, lets do the query dispatch and do the result checking
         * merge, Liked the map-reduce way ... 
        */
        $ret = false;
        foreach ( $shardingModels as $sharding ) {
            $pModel = $sharding['model'];
            $ret    = $pModel->update($data, $sharding['where'], $affected_rows) || $ret;
        }

        return $ret;
    }

    /**
     * update by primary key
     * 
     * @see #getById($_filed, $id)
    */
    public function updateById(&$data, $id, $affected_rows=true)
    {
        /*
         * check and parse the sharding model from the
         * specifield universal unique identifier
        */
        $pModel = $this->getShardingModelFromId($id);
        if ( $pModel == false ) {
            return false;
        }

        return $pModel->updateById($data, $id, $affected_rows);
    }

    /**
     * Set the value of the specifield field of the speicifled reocords
     *  in data table $this->table
     *
     * @param   $_field
     * @param   $_val
     * @param   $_where
     * @param   $affected_rows
     * @fragment support
    */
    public function set($_field, $_val, $_where, $affected_rows=true)
    {
        $data = array($_field => $_val);
        return $this->update($data, $_where, $affected_rows);
    }

    /**
     * set by primary key
     * 
     * @see #getById($_filed, $id)
     * @fragments support
    */
    public function setById($_field, $_val, $id, $affected_rows=true)
    {
        $data = array($_field => $_val);
        return $this->updateById($data, $id, $affected_rows);
    }

    /**
     * Increase the value of the specifield field of 
     *  the specifiled records in data table $this->table
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
     * @return  bool
    */
    public function increase($_field, $_offset, $_where)
    {
        $shardingModels = $this->__getQueryShardingModels($_where);
        if ( count($shardingModels) == 1 ) {
            $pModel = $shardingModels[0]['model'];
            return $pModel->increase($_field, $_offset, $shardingModels[0]['where']);
        }

        $ret = false;
        foreach ( $shardingModels as $sharding ) {
            $pModel = $sharding['model'];
            $ret    = $pModel->increase($_field, $_offset, $sharding['where']) || $ret;
        }

        return $ret;
    }

    /**
     * increase by primary_key
     *
     * @see #getById($_field, $id)
    */
    public function increaseById($_field, $_offset, $id)
    {
        $pModel = $this->getShardingModelFromId($id);
        if ( $pModel == false ) {
            return false;
        }

        return $pModel->increaseById($_field, $_offset, $id);
    }

    /**
     * reduce the value of the specifield field of the speicifled records
     *  in data table $this->table
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
    */
    public function reduce($_field, $_offset, $_where)
    {
        $shardingModels = $this->__getQueryShardingModels($_where);
        if ( count($shardingModels) == 1 ) {
            $pModel = $shardingModels[0]['model'];
            return $pModel->reduce($_field, $_offset, $shardingModels[0]['where']);
        }

        $ret = false;
        foreach ( $shardingModels as $sharding ) {
            $pModel = $sharding['model'];
            $ret    = $pModel->reduce($_field, $_offset, $sharding['where']) || $ret;
        }

        return $ret;
    }

    /**
     * reduce by primary_key
     *
     * @see #getById($_field, $id)
    */
    public function reduceById($_field, $_offset, $id)
    {
        $pModel = $this->getShardingModelFromId($id);
        if ( $pModel == false ) {
            return false;
        }

        return $pModel->reduceById($_field, $_offset, $id);
    }

    /**
     * Delete the specifield records
     *
     * @param   $_where
     * @fragments suport
    */
    public function delete($_where)
    {
        $shardingModels = $this->__getQueryShardingModels($_where);
        if ( count($shardingModels) == 1 ) {
            $pModel = $shardingModels[0]['model'];
            return $pModel->delete($shardingModels[0]['where']);
        }

        $ret = false;
        foreach ( $shardingModels as $sharding ) {
            $pModel = $sharding['model'];
            $ret    = $pModel->delete($sharding['where']) || $ret;
        }

        return $ret;
    }

    /**
     * delete by primary key
     *
     * @see #getById($_field, $id)
     * @frament suports
    */
    public function deleteById($id)
    {
        $pModel = $this->getShardingModelFromId($id);
        if ( $pModel == false ) {
            return false;
        }

        return $pModel->deleteById($id);
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
    public function setDebug($debug)
    {
        $this->_debug = $debug;
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
     * active the table view status
     *
     * @return  $this
    */
    public function openView()
    {
        $this->isView = true;
        return $this;
    }

    /**
     * disactive the table view status
     *
     * @return  $this
    */
    public function closeView()
    {
        $this->isView = false;
        return $this;
    }

    //----------------------------------------------------


    /**
     * get the write/insert operation sharding model
     *
     * @param   $data
     * @return  C_Model
    */
    private function __getWriteShardingModel($data=NULL)
    {
        if ( ! isset($data[$this->router]) ) {
            return false;
        }

        $sIdx  = self::__hash($data[$this->router]) % count($this->shardings);
        $mconf = $this->shardings[$sIdx];
        $mObj  = Loader::model($mconf[0], $mconf[1]);
        $this->resetModelAttr($mObj);
        $this->lastAcitveModel = $mObj;

        return $mObj;
    }

    /**
     * get the query operation sharding model list
     *
     * @param   $where
     * @return  Array
     * 
     * the returned array should be something like this:
     * array(
     *     array(
     *         'model'  => Model Object
     *         'where'  => query where
     *     )
     * )
    */
    protected function __getQueryShardingModels($where)
    {
        if ( ! isset($where[$this->router]) ) {
            return $this->__getAllShardingModels($where);
        }

        $shardings = NULL;
        $optimize_code = 0;
        $seed = trim($where[$this->router]);

        /*
         * $seed could be something like:
         * 1, array($this->router => "[=|>|<]int");
         * 2, array($this->router => "=val")        --optimize
         * 3, array($this->router => "!=val")
         * 4, array($this->router => "in()")        --optimize
         * 5, array($this->router => "not in()")
         * 6, array($this->router => "bettween s and e")
         * @Note:
         * And only '=' and 'in' could be optimized
        */
        if ( $seed[0] == '=' ) {
            $sedVal = trim(substr($seed, 1));
            if ( self::isStringQuoted($sedVal) ) {
                $sedVal = substr($sedVal, 1, strlen($sedVal) - 2);
            }

            $shardIdx = self::__hash($sedVal) % count($this->shardings);
            $optimize_code = 1;
        } else if (strlen($seed) > 3) {
            $first2 = strtolower(substr($seed, 0, 2));
            if ( strncmp($first2, 'in', 2) != 0 ) {
                return $this->__getAllShardingModels($where);
            }
            
            //parse the in item values
            $sIdx = strpos($seed, '(');
            if ( $sIdx === false ) {
                return $this->__getAllShardingModels($where);
            }
            $sIdx++;
            $eIdx = strrpos($seed, ')', -1);
            if ( $eIdx === false ) {
                return $this->__getAllShardingModels($where);
            }

            $vstr = substr($seed, $sIdx, $eIdx - $sIdx);
            if ( $vstr == "" ) {
                return $this->__getAllShardingModels($where);
            }

            $varr = explode(',', $vstr);

            //make the shardings
            $shardLen  = count($this->shardings);
            $shardings = array();
            foreach ( $varr as $v_item ) {
                $v_item = trim($v_item);
                if ( self::isStringQuoted($v_item) ) {
                    $v_item = substr($v_item, 1, strlen($v_item) - 2);
                }

                //count the sharding index
                $sIdx = self::__hash($v_item) % $shardLen;
                if ( ! isset($shardings["{$sIdx}"]) ) {
                    $shardings["{$sIdx}"] = array(
                        'items' => array(),
                        'sIdx'  => $sIdx
                    );
                }
                $shardings["{$sIdx}"]['items'][] = $v_item;
            }

            $optimize_code = 2;
        }


        $models = array();
        switch ($optimize_code) {
        case 1:
            $conf = $this->shardings[$shardIdx];
            $mObj = Loader::model($conf[0], $conf[1]);
            $this->resetModelAttr($mObj);
            $this->lastAcitveModel = $mObj;
            $models[] = array(
                'model' => $mObj,
                'where' => $where
            );
            break;
        case 2:
            foreach ( $shardings as $shard ) {
                $items = $shard['items'];
                $sIdx  = $shard['sIdx'];
                $conf  = $this->shardings[$sIdx];
                $mObj  = Loader::model($conf[0], $conf[1]);
                $this->resetModelAttr($mObj);
                $this->lastAcitveModel = $mObj;

                /*
                 * @Note: here we got to rewrite the query condition
                */
                $_where = $where;
                $cwhere = count($items) == 1 ? "={$items[0]}" : 'in('.implode(',', $items).')';
                $_where[$this->router] = $cwhere;
                $models[] = array(
                    'model' => $mObj,
                    'where' => $_where
                );
            }
            break;
        default:
            $models = $this->__getAllShardingModels($where);
            break;
        }

        return $models;
    }

    /**
     * get all sharding models
     *
     * @param   $where
     * @return  Array
    */
    private function __getAllShardingModels($where)
    {
        $models = array();
        foreach ( $this->shardings as $mconf ) {
            $mObj = Loader::model($mconf[0], $mconf[1]);
            $this->resetModelAttr($mObj);
            $this->lastAcitveModel = $mObj;
            $models[] = array(
                'model' => $mObj,
                'where' => $where
            );
        }

        return $models;
    }

    /**
     * reset the model attribtues from global setting
     *
     * @param   $model
    */
    private function resetModelAttr($model)
    {
        //check and set the onDuplicateKey handler
        if ( $this->_onDuplicateKey !== NULL ) {
            $model->onDuplicateKey($this->_onDuplicateKey);
        }

        //check and set the debug statue
        if ( $this->_debug !== NULL ) {
            $model->setDebug($this->_debug);
        }

        //check and set the read/write separate status
        if ( $this->_srw !== NULL ) {
            if ( $this->_srw ) $model->startSepRaw();
            else $model->closeSepRaw();
        }

        //check and set the fragment status
        if ( $this->isFragment !== NULL ) {
            if ( $this->isFragment ) $model->openFragment();
            else $model->closeFragment();
        }

        //check and set the views status
        if ( $this->isView !== NULL ) {
            if ( $this->isView ) $model->openView();
            else $model->closeView();
        }
    }

    /**
     * group the specifield data by the sharding hash value.
     *
     * @param   $data
     * @param   $genUid wether to generate and append the UID
     * @return  Array
    */
    protected function groupDataBySharding($data, $genUid=false)
    {
        $gDataSet = array();
        $modelSet = array();
        foreach ($data as $val) {
            // Check and append the global unique identifier.
            if ( $this->guidKey != NULL && $genUid == true ) {
                $val[$this->guidKey] = self::genUUID($val, $this->router);
            }

            if ( ! isset($val[$this->router]) ) {
                $this->routerError(true);
            }

            $seed = $val[$this->router];
            $sIdx = self::__hash($seed) % count($this->shardings);
            if ( ! isset($gDataSet[$sIdx]) ) {
                $mconf = $this->shardings[$sIdx];
                $mObj  = Loader::model($mconf[0], $mconf[1]);
                $this->resetModelAttr($mObj);
                $modelSet[$sIdx] = $mObj;
                $gDataSet[$sIdx] = array();
            }

            $gDataSet[$sIdx][] = $val;
        }

        return array($gDataSet, $modelSet);
    }

    /**
     * get the sharding from the specifield universal unique identifier
     *
     * @param   $id
     * @param   $willQuery will query be executed through the model
     * @return  Mixed false or an initialized C_Model instance
    */
    private function getShardingModelFromId($id, $willQuery=true)
    {
        if ( strlen($id) != 32 ) {
            return false;
        }

        $mask = hexdec(substr($id, 28, 4));
        if ( ($mask & 0x01) == 1 ) {
            $routerVal = hexdec(substr($id, 20, 8));
        } else {
            $routerVal = self::__hash($id);
        }

        $sIdx  = $routerVal % count($this->shardings);
        $mconf = $this->shardings[$sIdx];
        $mObj  = Loader::model($mconf[0], $mconf[1]);
        $this->resetModelAttr($mObj);

        //check and reset the last active model
        if ( $willQuery ) {
            $this->lastAcitveModel = $mObj;
        }

        return $mObj;
    }

    //------------------static tools function----------------------

    /**
     * bkdr hash algorithm
     *
     * @param   $str
     * @return  Integer hash value
    */
    private static function __hash($str)
    {
        $hval = 0;
        $len  = strlen($str);
    
        /*
         * 4-bytes integer we will directly take
         * its int value as the final hash value.
        */
        if ( $len <= 11 && is_numeric($str) ) {
            $hval = intval($str);
        } else {
            for ( $i = 0; $i < $len; $i++ ) {
                $hval = (int) ($hval * 1331 + (ord($str[$i]) % 127));
            }
        }
        
        return ($hval & 0x7FFFFFFF);
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

    /**
     * generate a universal unique identifier
     *
     * @param   $data
     * @param   $router
     * @return  String
    */
    private static function genUUID($data, $router)
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

        /*
         * 2, check and merge the router info insite
         * This is the key point
        */
        $embed = 0x00;
        $routerVal = NULL;
        if ( isset($data[$router]) == false ) {
            $routerVal = mt_rand(0, 0x7FFFFF);
        } else {
            $embed = 0x01;
            $routerVal = self::__hash($data[$router]);
        }

        return sprintf(
            "%08x%08x%0s%08x%04x", 
            $tsec, 
            $msec,
            $prefix, 
            $routerVal,
            mt_rand(0, 0xffff) | $embed
        );
    }

    /**
     * show the router error info
     *
     * @param   $exit
    */
    private function routerError($exit=false)
    {
        echo "Error: [" . __CLASS__ . "] router missing for sharding.\n";

        if ( $exit ) {
            exit();
        }
    }

}
?>
