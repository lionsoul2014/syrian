<?php
/**
 * super sebiont spark model implementation.
 * which implemented the common IModel interface.
 * All the spark model instance should extends from this.
 * 
 * @author  chenxin<chenxin619315@gmail.com>
*/

import('model.IModel');

//-----------------------------------------

class SparkModel implements IModel
{

    /**
     * spark server restful api base url
     *
     * @access  protected
    */
    protected   $baseUrl = null;

    /**
     * default spark database
     *
     * @access  protected
    */
    protected   $database = null;

    /**
     * mapping fields array
     *
     * @access  protected
    */
    protected   $fields = null;

    /**
     * Basic setting for the current model
     * the primary key field name
    */
    protected   $primary_key = null;

    /**
     * router field
     * @Note: not implmented for now
    */
    protected   $router = null;

    /**
     * @Note: this is a core function added at 2015-06-13
     * with this you could sperate the fields of you table 
     * so store them in different section
     *
     * @Note: for spark there is no need
    */
    protected   $fragments  = null;
    protected   $isFragment = false;

    /**
     * debug control mask
    */
    protected   $_debug     = false;
    protected   $lastErrno  = 0;
    protected   $lastError  = null;
    
    
    /**
     * default construct method
    */
    public function __construct()
    {
        //TODO:
        /*
         * Add $this->primary_key for the main key of the table
         * Set $this->fields = fields mapping
         * Set $this->router = field name
        */
    }

    /**
     * create the model mapping like creating the table in the DBMS
     * so, each model execute once before any operation
     *
     * @param   $setting
     * @return  boolean
    */
    public function mapping($setting=null)
    {
        if ( $this->fields == null ) {
            throw new Exception("model fields not setting");
        }

        /*
         * create the type mapping
         * convert the fields to spark DSL
        */
        $workload = $setting == null ? array() : $setting;
        $workload['mapping'] = $this->fields;

        $DSL = json_encode($workload);
        $ret = $this->_request('POST', $DSL, '_mapping', "dbName={$this->database}");
        if ( $ret == false ) {
            return false;
        }

        if ( isset($ret->error) ) {
            throw new Exception("mapper exception: {$ret->error->message}");
        }

        return true;
    }
    
    
    /**
     * parse the SQL-style query condition
     * We mean to make it Compatible with the SQL common model
     *
     * @Note: 
     * here we mean to make the sebiont spark works like a traditional
     * SQL database, so we design it to use the spark filter always.
     *
     * 01, array(field => '=val')
     * 02, array(field => '!=val')
     * 03, array(field => 'in(v1,v2,...)')       //in search
     * 04, array(field => 'not in(v1,v2,...)')   //in search
     * 05, array(field => 'like %value%')        //like match
     *
     * @param   $_where
     * @return  array(Spark DSL)
    */
    protected function parseSQLCompatibleQuery($_where)
    {
        $query_or  = null;
        $query_and = null;
        $query     = array(
            'must'      => array(),
            'should'    => array(),
            'must_not'  => array()
        );

        foreach ( $_where as $field => $value ) {
            if ( is_string($value) ) {
                $value = trim($value);

                //query value string basic length checking
                if ( ($len = strlen($value)) < 2 ) {
                    throw new Exception("Invalid query syntax for field '{$field}'");
                }
            } else if ($field[0] != '#') {
                throw new Exception("Invalid query syntax for field '{$field}'");
            }

            switch ( $field[0] ) {
            case '&':
                $branch = 'must';
                $field  = trim(substr($field, 1));
                break;
            case '|':
                $branch = 'should';
                $field  = trim(substr($field, 1));
                break;
            case '#':   //new branch
                if ( ! is_array($value) ) {
                    throw new Exception("Invalid query syntax for field '{$field}'");
                }

                $field  = strtoupper(trim(substr($field, 1)));
                if ( $field == 'OR' ) {
                    if ( $query_or == null ) $query_or = array();
                    $query_or[]  = $this->parseSQLCompatibleQuery($value);
                } else {
                    if ( $query_and == null ) $query_and = array();
                    $query_and[] = $this->parseSQLCompatibleQuery($value);
                }
                break;
            default:
                $branch = 'must';
                break;
            }

            //echo 'field: ', $field, ", ", $branch, "\n";
            if ( ! is_string($value) ) {
                continue;
            }

            $opcode = strtolower($value[0]);
            switch ( $opcode ) {
            /*
             * the '=' will be translated to the elasticsearch term query
             * @TODO: field data conversion may need to be actived
            */
            case '=':
                $query[$branch][] = array(
                    'term' => array(
                        //$field => $this->getFieldValue($field, trim(substr($value, 1)))
                        $field => trim(substr($value, 1))
                    )
                );
                break;
            /*
             * the '>\=', '<\=' will be translated to the elasticsearch range query
             * @TODO: field data conversion may need to be actived
            */
            case '>':
            case '<':
                $sIdx = 1;
                if ($value[1] == '=') {
                    $sIdx = 2;
                }

                $include = $sIdx > 1;
                if ( $opcode == '>' ) {
                    $limit = array(
                        //'from' => $this->getFieldValue($field, trim(substr($value, $sIdx))),
                        'from' => trim(substr($value, $sIdx)),
                        'include_lower' => $include
                    );
                } else {
                    $limit = array(
                        //'to' => $this->getFieldValue($field, trim(substr($value, $sIdx))),
                        'to' => trim(substr($value, $sIdx)),
                        'include_upper' => $include
                    );
                }

                $query[$branch][] = array(
                    'range' => array(
                        $field => $limit
                    )
                );
                break;
            /*
             * the '!=' will be translated to the elasticsearch bool not term query
             * @TODO: field data conversion may need to be actived
            */
            case '!':
                if ( $value[1] != '=' ) {
                    throw new Exception("Invalid != query syntax for field '{$field}'");
                }

                $query['must_not'][] = array(
                    'term' => array(
                        //$field => $this->getFieldValue($field, trim(substr($value, 2)))
                        $field => trim(substr($value, 2))
                    )
                );
                break;
            /*
             * the 'in(v1,v2...)' or 'not in(v1,v2...)' will be translated to the elasticsearch 
             * ids(primary key) or terms query
             * the single in item will be automatically convert to the term query
             * @TODO: field data conversion may need to be actived
            */
            case 'i':   //in query
            case 'n':   //not in query
                $sIdx = strpos ($value, '(');
                $eIdx = strrpos($value, ')');
                if ( $sIdx === FALSE || $eIdx === FALSE ) {
                    throw new Exception("Invalid in query syntax for field '{$field}'");
                }

                //there must at least 1 char bettween the '(' and the ')'
                if ( $eIdx - $sIdx < 2 ) {
                    throw new Exception("Invalid in query syntax for field '{$field}'");
                }

                $sIdx++;
                $items = explode(',', substr($value, $sIdx, $eIdx - $sIdx));
                $limit = null;
                if ( count($items) == 1 ) {
                    $limit = array(
                        'term' => array(
                            //$field => $this->getFieldValue($field, $items[0])
                            $field => $items[0]
                        )
                    );
                } else if ($this->primary_key == $field) {
                    //the default data type for _id is string
                    $limit = array(
                        'ids' => array(
                            'values' => $items
                        )
                    );
                } else {
                    $limit = array(
                        'terms' => array(
                            //$field => $this->getFieldValue($field, $items)
                            $field => $items
                        )
                    );
                }

                $query[$opcode=='n'?'must_not':$branch][] = $limit;
                break;
            /*
             * the 'like %value%' will be translated to the elasticsearch term query
             * or the prefix query for 'like value%'
             * cuz the like value wont be analysis in the traditional SQL-style DBMS
             *
             * @Note: like query only available for string fields so no data type convertion
            */
            case 'l':
                $syntax = trim(substr($value, 4));
                $length = strlen($syntax);
                if ( $length < 1  ) {
                    throw new Exception("Invalid like syntax for field '{$field}'");
                }

                //started with % ? then directly convert to the term query
                $limit = null;
                if ( $syntax[0] == '%' ) {
                    $limit = array(
                        'term' => array(
                            $field => str_replace('%', '', $syntax)
                        )
                    );
                } else if ($syntax[$length-1] == '%') {
                    $limit = array(
                        'prefix' => array(
                            $field => substr($syntax, 0, $length - 1)
                        )
                    );
                } else {
                    $limit = array(
                        'term' => array(
                            $field => $syntax
                        )
                    );
                }

                $query[$branch][] = $limit;
                break;
            }
        }

        //-------------------------------------------------------------
        //pre-process the parsed query result

        //regroup the query:
        //check if there is or query and merge all of them into the should query
        if ( ! empty($query['should']) ) {
            $must_len = count($query['must']);
            if ( $must_len == 1 ) {
                $query['should'][] = $query['must'][0];
                unset($query['must']);
            } else if ( $must_len > 1 ) {
                $query['should'][] = array(
                    'bool' => array(
                        'must' => $query['must']
                    )
                );
                unset($query['must']);
            }
        }

        //clear the empty sub-query item
        if ( empty($query['must']) ) unset($query['must']);
        if ( empty($query['should']) ) unset($query['should']);
        if ( empty($query['must_not']) ) unset($query['must_not']);
        if ( $query_and == null && $query_or == null ) {
            return array(
                'bool' => $query
            );
        }



        //regroup the query
        //1, merge the query and the query_and branch
        if ( $query_and != null  ) {
            foreach ( $query_and as $val ) {
                $squery = $val['bool'];
                if ( ! empty($squery['must']) ) {
                    if ( ! isset($query['must']) ) $query['must'] = array();
                    foreach ( $squery['must'] as $must ) {
                        $query['must'][] = $must;
                    }
                }

                if ( ! empty($squery['must_not']) ) {
                    if ( ! isset($query['must_not']) ) $query['must_not'] = array();
                    foreach ( $squery['must_not'] as $must_not ) {
                        $query['must_not'][] = $must_not;
                    }
                }

                if ( ! empty($squery['should']) ) {
                    if ( ! isset($query['should']) ) $query['should'] = array();
                    foreach ( $squery['should'] as $should ) {
                        $query['should'][] = $should;
                    }
                }
            }

            $query = array(
                'bool' => $query
            );
        }

        //2, check and regroup the query_or branch
        if ( $query_or != null ) {
            $query_or[] = $query;
            $query = array(
                'bool' => array(
                    'should' => $query_or
                )
            );
        }

        //pre-process the parsed query result
        foreach ( $query as $key => $val ) {
            if ( empty($val) ) {
                unset($query[$key]);
            }
        }

        return $query;
    }

    /**
     * get the query DSL
     * parse the sql compatible syntax, orde, limit to get the final spark query DSL
     *
     * @param   $_where
     * @param   $_order
     * @param   $_limit
     * @param   $_query
     * @return  String query DSL
    */
    protected function getQueryDSL($_filter, $_order, $_limit, $_query=null)
    {
        /*
         * filter condition parse
         * default to the sql compatible style syntax parser
         * and default to the match_all elasticsearch query for empty filter
         *  and if there is no complex fulltext query defined
        */
        $filter = null;
        if ( $_filter != null ) {
            $filter = $this->parseSQLCompatibleQuery($_filter);
        }

        /*
         * query check and define
        */
        $query = null;
        if ( $_query == null ) {
            if ( $filter == null ) {
                $query = array(
                    'match_all' => new StdClass()
                );
            }
        } else if ( isset($_query['field']) && isset($_query['query']) ) {
            $qdata = isset($_query['option']) ? $_query['option'] : array();
            $qdata['query'] = $_query['query'];
            switch ( $_query['type'] ) {
            case 'term':
                $query = array(
                    'term' => array($_query['field'] => $_query['query'])
                );
                break;
            case 'terms':
                $query = array(
                    'terms' => array($_query['field'] => $_query['query'])
                );
                break;
            case 'match':
                $query = array(
                    'match' => array($_query['field'] => $qdata)
                );
                break;
            case 'multi_match':
                $qdata['fields'] = $_query['field'];
                $query = array('multi_match' => $qdata);
                break;
            case 'query_string':
                $field_name = is_array($_query['field']) ? 'fields' : 'default_field';
                $qdata[$field_name] = $_query['field'];
                $query = array('query_string' => $qdata);
                break;
            }
        } else {
            throw new Exception("Missing key 'field' or 'query' for query define");
        }

        /*
         * check and create the sorting
         * order with a style like array(
         *  array(Id => desc),
         *  array(_score => desc)
         * )
        */
        $sort = null;
        if ( $_order != null ) {
            $sort = array();
            foreach ( $_order as $field => $order ) {
                $sort[] = array($field => $order);
            }
        }

        /*
         * check and define the limit
         * limit often with style like '0,20'
        */
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

        /*
         * define the query array
         * default to use the constant_score filter
         * and this will make the elasticsearch works like the traditional database
        */
        $boolQuery = isset($_query['setting']) ? $_query['setting'] : array();
        if ( $query  != null ) $boolQuery['must']   = $query;
        if ( $filter != null ) $boolQuery['filter'] = $filter;
        $queryDSL = array(
            'query' => array('bool' => $boolQuery)
        );

        //check and define the query sort
        if ( $sort != null ) {
            $queryDSL['sort'] = $sort;
        }

        //check and define the from, size attributes
        if ( $from >= 0 ) $queryDSL['from'] = $from;
        if ( $size >  0 ) $queryDSL['size'] = $size;

        return self::array2Json($queryDSL);
    }

    
    
    /**
     * get the last active SparkModel object
     *
     * @return  SparkModel
    */
    public function getLastActiveModel()
    {
        return $this;
    }

    /**
     * execute the specifield query command
     *
     * @param   $_query
     * @param   $opt code
     * @param   $_row return the affected rows?
     * @return  Mixed
    */
    public function execute($_query, $opt=0, $_row=false)
    {
        throw new Exception("Not implemented yet.");
    }

    /**
     * get the total count
     *
     * @param   $_where
     * @param   $_group
     * @return  int
    */
    public function totals($_where=null, $_group=null)
    {
        return 0;
    }

    /**
     * Get a vector from the specifiel source
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
     * @param   $_group
    */
    public function getList($_fields, $_where=null, $_order=null, $_limit=null, $_group=null)
    {
    }

    /**
     * get a specifiled record from the specifield table
     *
     * @param   $Id
     * @param   $_fields
    */
    public function get($_fields, $_where)
    {
    }

    //get by primary key
    public function getById($_fields, $id)
    {
    }

    //-------------------------------------------------------------------------

    /**
     * Add an new record to the data source
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Mixed false or row_id
    */
    public function add($data, $onDuplicateKey=null)
    {
    }

    /**
     * batch add with no fragments support
     *
     * @param   $data
    */
    public function batchAdd($data, $onDuplicateKey=null)
    {
    }

    /**
     * Conditioan update
     *
     * @param   $data
     * @param   $_where
     * @param   $affected_rows
     * @return  Mixed
    */
    public function update($data, $_where, $affected_rows=true)
    {
    }

    //update by primary key
    public function updateById($data, $id, $affected_rows=true)
    {
    }

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
    public function set($_field, $_val, $_where, $affected_rows=true)
    {
    }

    //set by primary key
    //@fragments support
    public function setById($_field, $_val, $id, $affected_rows=true)
    {
    }

    /**
     * Increase the value of the specifield field of 
     *  the specifiled records in data source
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
     * @return  bool
    */
    public function increase($_field, $_offset, $_where)
    {
        throw new Exception("Not implemented yet.");
    }

    //increase by primary_key
    public function increaseById($_field, $_offset, $id)
    {
        throw new Exception("Not implemented yet.");
    }

    /**
     * decrease the value of the specifield field of the speicifled records
     *  in data source
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
     * @return  Mixed
    */
    public function decrease($_field, $_offset, $_where)
    {
        throw new Exception("Not implemented yet.");
    }

    //decrease by primary_key
    public function decreaseById($_field, $_offset, $id)
    {
        throw new Exception("Not implemented yet.");
    }

    /**
     * Delete the specifield records
     *
     * @param   $_where
     * @param   $frag_recur
     * @fragments suport
    */
    public function delete($_where, $frag_recur=true)
    {
    }

    //delete by primary key
    //@frament suports
    public function deleteById($id)
    {
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

    public function getLastErrno()
    {
        return $this->lastErrno;
    }

    public function getLastError()
    {
        return $this->lastError;
    }
    
    /**
     * data types standardization
     * @Note: make sure this->fields is not null before you invoke this method
     *
     * @param   $data source data
    */
    protected function stdDataTypes(&$data)
    {
        foreach ($this->fields as $key => $attr) {
            if ( ! isset($data[$key]) ) {
                continue;
            }

            //1. get the original data type
            $value = &$data[$key];
            switch ( $attr['type'] ) {
            case 'long':
            case 'integer':
            case 'short':
            case 'byte':
                if ( ! is_long($value) )    $value = intval($value);
                break;
            case 'float':
                if ( ! is_float($value) )   $value = floatval($value);
                break;
            case 'double':
                if ( ! is_double($value) )  $value = doubleval($value);
                break;
            case 'boolean':
                if ( ! is_bool($value) ) settype($value, 'boolean');
                break;
            }
        }
    }

    /*
     * get the standardization field value
     *
     * @param   $field
     * @param   $value
     * @return  Mixed(the type that the field defined)
    */
    protected function getFieldValue($field, $value)
    {
        if ( ! isset($this->fields[$field]) ) {
            return $value;
        }

        $type = $this->fields[$field]['type'];
        switch ( $type ) {
        case 'long':
        case 'integer':
        case 'short':
        case 'byte':
            if ( is_array($value) ) {
                foreach ( $value as $key => $val ) {
                    if ( is_long($val) ) continue;
                    $value[$key] = intval($val);
                }
            } else if ( ! is_long($value) ) {
                $value = intval($value);
            }
            break;
        case 'float':
            if ( is_array($value) ) {
                foreach ( $value as $key => $val ) {
                    if ( is_float($val) ) continue;
                    $value[$key] = floatval($val);
                }
            } else if ( ! is_float($value) ) {
                $value = floatval($value);
            }
            break;
        case 'double':
            if ( is_array($value) ) {
                foreach ( $value as $key => $val ) {
                    if ( is_double($val) ) continue;
                    $value[$key] = doubleval($val);
                }
            } else if ( ! is_double($value) ) {
                $value = doubleval($value);
            }
            break;
        case 'boolean':
            if ( is_array($value) ) {
                foreach ( $value as $key => $val ) {
                    if ( is_bool($val) ) continue;
                    settype($val, 'boolean');
                    $value[$key] = $val;
                }
            } else if ( ! is_bool($value) ) {
                settype($value, 'boolean');
            }
            break;
        }

        return $value;
    }

    /*
     * do final elasticsearch http query
     *
     * @param   $method (uppercase)
     * @param   $dsl
     * @param   $uri
     * @param   $args
     * @param   $_assoc
     * @param   $chk_error
     * @return  Mixed(Object or false)
    */
    protected function _request(
        $method, $dsl, $uri, $args=null, $_assoc=false, $chk_error=true)
    {
        $qstring = null;
        $baseUrl = "{$this->baseUrl}/{$uri}";
        if ( $args != null ) {
            if ( is_array($args) ) {
                $parts = array();
                foreach ( $args as $key => $val ) {
                    $parts[] = "{$key}={$val}";
                }
                $qstring = implode('&', $parts);
            } else {
                $qstring = $args;
            }

            $baseUrl = "{$baseUrl}?{$qstring}";
        }

        //format the data
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $baseUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/plain', 
            'User-Agent: spark php client/1.0'
        ));

        if ( $dsl != null ) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $dsl);
        }

        //do the final http request
        $ret = curl_exec($curl);
        curl_close($curl);

        if ( $this->_debug ) {
            $output = <<<EOF
url: {$baseUrl}
method: {$method}
DSL: {$dsl}
return: {$ret}\n
EOF;
            echo $output;
        }

        if ( $ret == false ) {
            return false;
        }

        $json = json_decode($ret, $_assoc);
        if ( $json == null ) {
            return false;
        }

        // check and do the error analysis and record
        $has_error = $chk_error && ($_assoc 
            ? isset($json['error']) : isset($json->error));
        if ( $has_error ) {
            if ( $_assoc ) {
                $jError = $json['error'];
                $this->lastErrno = $jError['status'];
                $this->lastError = $jError['message'];
            } else {
                $jError = $json->error;
                $this->lastErrno = $jError->status;
                $this->lastError = $jError->message;
            }
            return false;
        }

        return $json;
    }

    /**
     * generate a universal unique identifier
     * Override it to make amazing things
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
        $prefix = null;
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
     * initialize the current model from the specified mapping
     * configuration that defined in config/database.conf.php
     *
     * @param   $section
     * @return  SparkModel
    */
    protected function _InitFromConfigByKey($section)
    {
        //Load the elasticsearch config
        $conf = config("database#{$section}");
        if ( $conf == false ) {
            throw new Exception("Invalid db section {$section}");    
        }

        $this->database = $conf['database'];
        $this->baseUrl  = "http://{$conf['host']}:{$conf['port']}";

        return $this;
    }

}
?>
