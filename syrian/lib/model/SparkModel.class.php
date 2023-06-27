<?php
/**
 * super sebiont spark model implementation.
 * which implemented the common IModel interface.
 * All the spark model instance should extends from this.
 *
 * @Note: cuz the unique algorithm can not guarantee
 *  cluster unique, we suggest u set the primary_key 
 * and use the source data's unique id.
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
     * 02, array(field => '>=|<=val')            //range filter
     * 03, array(field => '!=val')
     * 04, array(field => 'in(v1,v2,...)')       //in search
     * 05, array(field => 'not in(v1,v2,...)')   //in search
     * 06, array(field => 'like %value%')        //like match
     *
     * @param   $_where
     * @return  array(Spark DSL)
    */
    protected function parseSQLCompatibleQuery($_where)
    {
        $filter = array(
            'must'     => array(),
            'should'   => array(),
            'must_not' => array()
        );

        foreach ( $_where as $field => $value ) {
            if ( is_string($value) ) {
                $value = trim($value);

                //query value string basic length checking
                if ( ($len = strlen($value)) < 2 ) {
                    throw new Exception("Invalid query syntax for field '{$field}'");
                }
            } else {
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
            default:
                $branch = 'must';
                break;
            }

            if ( ! is_string($value) ) {
                continue;
            }

            $opcode = strtolower($value[0]);
            switch ( $opcode ) {
            /*
             * the '=' will be translated to the spark filter
             * @TODO: field data conversion may need to be actived
            */
            case '=':
                $query[$branch][] = array(
                    //$field => $this->getFieldValue($field, trim(substr($value, 1)))
                    $field => trim(substr($value, 1))
                );
                break;
            /*
             * the '>\=', '<\=' will be translated to the spark range query
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
             * the '!=' will be translated to the spark must_not filter
             * @TODO: field data conversion may need to be actived
            */
            case '!':
                if ( $value[1] != '=' ) {
                    throw new Exception("Invalid != query syntax for field '{$field}'");
                }

                $query['must_not'][] = array(
                    //$field => $this->getFieldValue($field, trim(substr($value, 2)))
                    $field => trim(substr($value, 2))
                );
                break;
            /*
             * the 'in(v1,v2...)' or 'not in(v1,v2...)' will be translated to the spark 
             * must or must not filter
             * @TODO: field data conversion may need to be actived
            */
            case 'i':   //in query
            case 'n':   //not in query
                $sIdx = strpos ($value, '(');
                $eIdx = strrpos($value, ')');
                if ( $sIdx === FALSE || $eIdx === FALSE ) {
                    throw new Exception("Invalid in query syntax for field '{$field}'");
                }

                // there must at least 1 char bettween the '(' and the ')'
                if ( $eIdx - $sIdx < 2 ) {
                    throw new Exception("Invalid in query syntax for field '{$field}'");
                }

                $sIdx++;
                $items = explode(',', substr($value, $sIdx, $eIdx - $sIdx));
                $limit = array(
                    //$field => $this->getFieldValue($field, $items[0])
                    $field => $items
                );
                $query[$opcode=='n'?'must_not':$branch][] = $limit;
                break;
            /*
             * the 'like %value%' will be translated to the filter
             * cuz the like value wont be analysis in the traditional SQL-style DBMS
             * @Note: like query only available for string fields so no data type convertion
            */
            case 'l':
                $syntax = trim(substr($value, 4));
                $length = strlen($syntax);
                if ( $length < 1  ) {
                    throw new Exception("Invalid like syntax for field '{$field}'");
                }

                //started with % ? then directly convert to the term query
                $query[$branch][] = array(
                    $field => str_replace('%', '', $syntax)
                );
                break;
            default:
                $query[$branch][] = array(
                    //$field => $this->getFieldValue($field, trim($value))
                    $field => trim($value)
                );
                break;
            }
        }

        return $query;
    }

    /**
     * get the query DSL
     * parse the sql compatible syntax, orde, limit to get the final spark query DSL
     *
     * @param   $_where
     * @param   $_match
     * @param   $_order
     * @param   $_limit
     * @param   $_group
     * @return  String query DSL
    */
    protected function getQueryDSL($_filter, $_match, $_order, $_group, $_limit)
    {
        $queryDSL = array();

        /*
         * filter condition parse
         * default to the sql compatible style syntax parser
         * and default to the match_all spark query with empty filter
         * and if there is no complex fulltext query defined
        */
        if ( $_filter != null ) {
            $queryDSL['filter'] = $this->parseSQLCompatibleQuery($_filter);
        }

        /*
         * query match check and define
        */
        if ( $_match == null ) {
            // default to with no match
        } else if ( isset($_match['field']) && isset($_match['query']) ) {
            $queryDSL['match'] = array(
                'min_score' => isset($_match['min_score']) ? $_match['min_score'] : 0,
                'query' => array(
                    array($_match['field'] => $_match['query'])
                )
            );
        } else {
            throw new Exception("Missing key 'field' or 'query' for match define");
        }

        /*
         * check and create the sorting
         * order with a style like array(
         *  array(Id => desc),
         *  array(_score => desc)
         * )
        */
        if ( $_order != null ) {
            $sort = array();
            foreach ( $_order as $field => $order ) {
                $sort[] = array($field => $order);
            }

            $queryDSL['sort'] = $sort;
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

        // check and append the group field
        // check and define the from, size attributes
        if ( $_group != null) $queryDSL['group'] = $_group;
        if ( $from >= 0 ) $queryDSL['from'] = $from;
        if ( $size >  0 ) $queryDSL['size'] = $size;

        return json_encode($queryDSL);
    }

    /**
     * get the query source fields arguments
     *
     * @param   $_fields
     * @return  String
    */
    protected function getQueryFieldArgs($_fields)
    {
        if ( $_fields === false ) {
            return '_source=false';
        }

        /*
         * query fields checking and pre-process
         * @Note: empty fields will cause global _fields fetch
        */
        $field_string = null;
        if ( is_array($_fields) ) {
            $field_string = implode(',', $_fields);
        } else {
            $field_string = trim($_fields);
        }

        $args = '_source=true';
        if ( strlen($field_string) > 0 ) {
            $args = $field_string[0]=='*' ? null : "_source={$field_string}";
        }

        return $args;
    }

    /**
     * build the results from the returned _search query parsed Array
     *
     * @param   $json
     * @param   $srcMode
     * @return  Array
    */
    protected function getQuerySets($json, $srcMode)
    {
        $ret = array();
        if ($srcMode == true) {
            foreach ($json['hits'] as $hit) {
                $ret[] = $hit['_source'];
            }
        } else {
            $ret['took']  = $json['took'];
            $ret['total'] = $json['total'];
            $ret['hits']  = array();
            foreach ($json['hits'] as $hit) {
                $ret['hits'][] = array(
                    '_db'       => $hit['_db'],
                    '_id'       => $hit['_id'],
                    '_score'    => $hit['_score'],
                    '_qm_rate'  => $hit['_qm_rate'],
                    '_dm_rate'  => $hit['_dm_rate']
                );
            }
        }

        return $ret;
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
     * execute the specified query command
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
     * directly DSL query support implementaion
     *
     * @param   $_fields
     * @param   $dsl
     * @param   $format auto format the query result
     * @return  Mixed
    */
    public function query($_fields, $dsl, $format=true)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $args = "dbName={$this->database}&{$_src}";
        $json = $this->_request('POST', $dsl, "_search?{$args}", null, true);
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 0.00025,
         *  "scanned": 2,
         *  "total": 2,
         *  "hits": [
         *      {
         *          "_db": "corpus",
         *          "_id": 2,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "video-music",
         *              "payload": "0",
         *              "scene_id": 2,
         *              "id": 2,
         *              "app_id": 2,
         *              "content": "我想看 :artist 的 电影"
         *          }
         *      },
         *      {
         *          "_db": "corpus",
         *          "_id": 1,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "view-music",
         *              "payload": "0",
         *              "scene_id": 1,
         *              "id": 1,
         *              "app_id": 1,
         *              "content": "我想听 :artist 的 歌"
         *          }
         *      }
         *  ]
         * }
         *
        */

        if (empty($json)) {
            return false;
        }

        $fJson = $json[0];
        if (! isset($fJson['hits']) || empty($fJson['hits'])) {
            return false;
        }

        return $format ? $this->getQuerySets(
            $fJson, $_fields===false ? false : true
        ) : $fJson['hits'];
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
        $_DSL = $this->getQueryDSL($_where, null, null, $_group, null);
        $json = $this->_request('POST', $_DSL, "_count?dbName={$this->database}");
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 0.00025,
         *  "scanned": 2,
         *  "total": 2,
         * }
         *
        */

        return isset($json->total) ? $json->total : 0;
    }

    /**
     * Get a vector from the specified source
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
     * @param   $_group
     * @param   Mixed false for failed or the returning Array
    */
    public function getList($_fields, $_where=null, $_order=null, $_limit=null, $_group=null)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $args = "dbName={$this->database}&{$_src}";
        $_DSL = $this->getQueryDSL($_where, null, $_order, $_group, $_limit);
        $json = $this->_request('POST', $_DSL, "_search?{$args}", null, true);
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 0.00025,
         *  "scanned": 2,
         *  "total": 2,
         *  "hits": [
         *      {
         *          "_db": "corpus",
         *          "_id": 2,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "video-music",
         *              "payload": "0",
         *              "scene_id": 2,
         *              "id": 2,
         *              "app_id": 2,
         *              "content": "我想看 :artist 的 电影"
         *          }
         *      },
         *      {
         *          "_db": "corpus",
         *          "_id": 1,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "view-music",
         *              "payload": "0",
         *              "scene_id": 1,
         *              "id": 1,
         *              "app_id": 1,
         *              "content": "我想听 :artist 的 歌"
         *          }
         *      }
         *  ]
         * }
         *
        */

        if (empty($json)) {
            return false;
        }

        $fJson = $json[0];
        if (! isset($fJson['hits']) || empty($fJson['hits'])) {
            return false;
        }

        return $this->getQuerySets(
            $fJson, $_fields===false ? false : true
        );
    }

    /**
     * match query interface do the spark complex score match.
     * for each matched document spark will mark a related scorer by
     * by the scorer define during mapping setted by scorer.
     *
     * @param   $_fields
     * @param   $_filter SQL compatible query filter
     * @param   $_match
     * @param   $_order
     * @param   $_limit
     * @param   $_group
     * @return  Mixed(Array or false)
    */
    public function match(
        $_fields, $_filter, $_match, $_order=null, $_limit=null, $_group=null)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $args = "dbName={$this->database}&{$_src}";
        $_DSL = $this->getQueryDSL($_filter, $_match, $_order, $_group, $_limit);
        $json = $this->_request('POST', $_DSL, "_search?{$args}", null, true);
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 0.00025,
         *  "scanned": 2,
         *  "total": 2,
         *  "hits": [
         *      {
         *          "_db": "corpus",
         *          "_id": 2,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "video-music",
         *              "payload": "0",
         *              "scene_id": 2,
         *              "id": 2,
         *              "app_id": 2,
         *              "content": "我想看 :artist 的 电影"
         *          }
         *      },
         *      {
         *          "_db": "corpus",
         *          "_id": 1,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "view-music",
         *              "payload": "0",
         *              "scene_id": 1,
         *              "id": 1,
         *              "app_id": 1,
         *              "content": "我想听 :artist 的 歌"
         *          }
         *      }
         *  ]
         * }
        */

        if (empty($json)) {
            return false;
        }

        $fJson = $json[0];
        if (! isset($fJson['hits']) || empty($fJson['hits'])) {
            return false;
        }

        //----------------------------------------------------
        // pre-process the returning data

        $ret = array(
            'took'  => $fJson['took'],
            'total' => $fJson['total']
        );

        if ($_fields != false) {
            $ret['hits'] = $fJson['hits'];
        } else {
            $ret['hits'] = array();
            foreach ($fJson['hits'] as $hit) {
                $ret['hits'][] = array(
                    '_db'      => $hit['_db'],
                    '_id'      => $hit['_id'],
                    '_score'   => $hit['_score'],
                    '_qm_rate' => $hit['_qm_rate'],
                    '_dm_rate' => $hit['_dm_rate']
                );
            }
        }

        return $ret;
    }

    /**
     * get a specified record from the specified table
     *
     * @param   $Id
     * @param   $_fields
     * @return  Mixed false for failed or Array
    */
    public function get($_fields, $_where)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $args = "dbName={$this->database}&{$_src}";
        $_DSL = $this->getQueryDSL($_where, null, null, null, 10);
        $json = $this->_request('POST', $_DSL, "_search?{$args}", null, true);
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 0.00025,
         *  "scanned": 2,
         *  "total": 2,
         *  "hits": [
         *      {
         *          "_db": "corpus",
         *          "_id": 2,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "video-music",
         *              "payload": "0",
         *              "scene_id": 2,
         *              "id": 2,
         *              "app_id": 2,
         *              "content": "我想看 :artist 的 电影"
         *          }
         *      },
         *      {
         *          "_db": "corpus",
         *          "_id": 1,
         *          "_score": null,
         *          "_qm_rate": null,
         *          "_dm_rate": null,
         *          "_match": {
         *              "tokens": []
         *          },
         *          "_source": {
         *              "user_id": 1,
         *              "condition_input": "view-music",
         *              "payload": "0",
         *              "scene_id": 1,
         *              "id": 1,
         *              "app_id": 1,
         *              "content": "我想听 :artist 的 歌"
         *          }
         *      }
         *  ]
         * }
         *
        */

        if (empty($json)) {
            return false;
        }

        $fJson = $json[0];
        if (! isset($fJson['hits']) || empty($fJson['hits'])) {
            return false;
        }

        $hit = $fJson['hits'][0];
        return $_fields == false ? array(
            '_db'      => $hit['_db'],
            '_id'      => $hit['_id'],
            '_score'   => $hit['_score'],
            '_qm_rate' => $hit['_qm_rate'],
            '_dm_rate' => $hit['_dm_rate']
        ) : $hit['_source'];
    }

    // get by primary key
    public function getById($_fields, $id)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $args = "dbName={$this->database}&id={$id}&{$_src}";
        $json = $this->_request('GET', null, "_get?{$args}", null, true);
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_id": 1,
         *  "took": 0.00004,
         *  "found": true,
         *  "_source": {
         *      "user_id": 1,
         *      "condition_input": "view-music",
         *      "payload": "0",
         *      "scene_id": 1,
         *      "id": 1,
         *      "app_id": 1,
         *      "content": "我想听 :artist 的 歌"
         *  }
         * }
        */

        if (isset($json['error']) || $json['found'] == false) {
            return false;
        }

        if ($_fields == false) {
            return array(
                '_db'       => $json['_db'],
                '_id'       => $json['_id'],
                '_score'    => 0.0,
                '_qm_rate'  => 1.0,
                '_dm_rate'  => 1.0
            );
        }

        return $json['_source'];
    }

    //-------------------------------------------------------------------------

    /**
     * Add an new record to the data source
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Mixed false or the newly added row id
    */
    public function add($data, $onDuplicateKey=null)
    {
        if ( $this->primary_key == null ) {
            $id = $this->genUUID($data);
        } else if ( isset($data[$this->primary_key]) ) {
            $id = $data[$this->primary_key];
        } else {
            throw new Exception("Missing mapping for {$this->primary_key} in the source data");
        }

        // stdlize the data types
        // do the data type convertion
        $this->stdDataTypes($data);
        $_DSL = json_encode($data);
        $json = $this->_request('POST', $_DSL, "_index?dbName={$this->database}&id={$id}");
        if ($json == false) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_db": "corpus",
         *  "_id": 1,
         *  "took": 0.001
         *  "created": true
         * }
        */
        if (isset($json->error)) {
            return false;
        }

        return isset($json->_id) ? $json->_id : $id;
    }

    /**
     * batch add with no fragments support
     *
     * @param   $data
     * @return  false for failed or the affected rows
    */
    public function batchAdd($data, $onDuplicateKey=null)
    {
        $workload = array();
        foreach ( $data as $val ) {
            if ( $this->primary_key == null ) {
                $id = $this->genUUID($val);
            } else if ( isset($val[$this->primary_key]) ) {
                $id = $val[$this->primary_key];
            } else {
                throw new Exception("Missing mapping for {$this->primary_key} in the source data");
            }

            $workload[] = "{\"type\":\"index\",\"_db\":\"{$this->database}\",\"_id\":{$id}}";
            // $workload[] = "{\"delete\":{\"_db\":\"{$this->database}\",\"_id\":\"{$id}\"}}";

            // do the data types conversion
            $this->stdDataTypes($val);
            $workload[] = json_encode($val);
        }

        $_DSL = implode("\n", $workload);
        $json = $this->_request('POST', $_DSL, '_bulk');
        if ($json == false || ! isset($json->items)) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 0.01,
         *  "items": [{
         *      "index": {
         *          "_db": "db name",
         *          "_id": 1,
         *          "created": true
         *      }
         *  }, {
         *      "delete": {
         *          "_db": "db name",
         *          "_id": 1,
         *          "found": true
         *      }
         *  }, {
         *      "index": {
         *          "error": {
         *              "status": 400,
         *              "type": "type_exception",
         *              "message": "exception message"
         *          }
         *      }
         *  }]
         * }
        */

        $ok_count = 0;
        foreach ($json->items as $item) {
            if (isset($item->_id)) {
                $ok_count++;
            }
        }

        return $ok_count;
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
        throw new Exception("Not implemented yet.");
    }

    //update by primary key
    public function updateById($data, $id, $affected_rows=true)
    {
        throw new Exception("Not implemented yet.");
    }

    /**
     * Set the value of the specified field of the speicifled reocords
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
        throw new Exception("Not implemented yet.");
    }

    //set by primary key
    //@fragments support
    public function setById($_field, $_val, $id, $affected_rows=true)
    {
        throw new Exception("Not implemented yet.");
    }

    /**
     * Increase the value of the specified field of 
     *  the specified records in data source
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
     * decrease the value of the specified field of the speicifled records
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
     * Delete the specified records
     *
     * @param   $_where
     * @param   $frag_recur
     * @param   $affected_rows
     * @fragments suport
    */
    public function delete($_where, $frag_recur=true, $affected_rows=true)
    {
        throw new Exception("Not implemented yet.");
    }

    //delete by primary key
    //@frament suports
    public function deleteById($id)
    {
        throw new Exception("Not implemented yet.");
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
                if ( ! is_long($value) ) {
                    $value = intval($value);
                }
                break;
            case 'float':
                if ( ! is_float($value) ) {
                    $value = floatval($value);
                }
                break;
            case 'double':
                if ( ! is_double($value) ) {
                    $value = doubleval($value);
                }
                break;
            case 'boolean':
                if ( ! is_bool($value) ) {
                    settype($value, 'boolean');
                }
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
            'Content-Type: application/json', 
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
        if ($json == null) {
            return false;
        }

        // check and do the error analysis and record
        $has_error = $chk_error && ($_assoc ? isset($json['error']) : isset($json->error));
        if ($has_error) {
            if ($_assoc) {
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
     * generate a universal unique 8-bytes numeric id
     * U may Override it to make amazing things ...
     *
     * @Note:
     * basicaly at the some node this could be unique
     * under a cluster environment we could not 
     *  guarantee the generated id is unique.
     *
     * @param   $data   original data
     * @return  long
    */
    protected function genUUID($data)
    {
        $tArr = explode(' ', microtime());
        $tsec = $tArr[1];
        $msec = $tArr[0];
        if ( ($sIdx = strpos($msec, '.')) !== false ) {
            $msec = substr($msec, $sIdx + 1);
        }

        // return sprintf(
        //     "%08x%08x%0s%04x", 
        //     $tsec, 
        //     $msec,
        //     $prefix, 
        //     mt_rand(0, 0xffff)
        // );

        return (
            ($tsec << 32) | ($msec << 8) | mt_rand(0, 0xff)
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
