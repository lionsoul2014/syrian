<?php
/**
 * common model for elasticsearch with
 *  the common model interface implemented
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

import('model.IModel');

//---------------------------------------------------

class ElasticSearchModel implements IModel
{
    /**
     * Avalable request methods mapping
     *
     * @access  protected
    */
    protected static $methods = array(
        "PUT"       => IModel::ADD_OPT,
        "DELETE"    => IModel::DELETE_OPT, 
        "GET"       => IModel::QUERY_OPT,
        "POST"      => IModel::UPDATE_OPT,
        "HEAD"      => IModel::QUERY_OPT
    );

    /**
     * elasticsearch server restful api base url
     *
     * @access  protected
    */
    protected   $baseUrl = NULL;

    /**
     * default elasticsearch data index
     *
     * @access  protected
    */
    protected   $index = NULL;

    /**
     * default elasticsearch table/type
    */
    protected   $type = NULL;

    /**
     * mapping fields array
     *
     * @access  protected
    */
    protected   $fields = NULL;

    /**
     * Basic setting for the current model
     * the primary key field name
    */
    protected   $primary_key    = NULL;

    /**
     * router field
     * @Note: not implmented for now
    */
    protected   $router = NULL;

    /**
     * query update/delete operation batch traffic
    */
    protected   $deleteTraffic = 200;
    protected   $updateTraffic = 100;

    /**
     * @Note: this is a core function added at 2015-06-13
     * with this you could sperate the fields of you table 
     *     so store them in different section
     *
     * @Note: for es there is no need
    */
    protected   $fragments  = NULL;
    protected   $isFragment = false;

    /**
     * debug control mask
    */
    protected   $_debug     = false;

    //----------------------------------------------

    /**
     * default construct method
    */
    public function __construct()
    {
        //TODO:
        /*
         * Add $this->primary_key for the main key of the table
         * Set $this->type = elasticsearch type name
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
    public function mapping($setting=NULL)
    {
        if ( $this->fields == NULL ) {
            throw new Exception("model fields not setting");
        }

        //1, check the existence of the index first
        $exists  = $this->_request('GET', NULL, $this->index);
        if ( $exists == false ) {   //hoops, something went wrong
            return false;
        }

        //2, create the index or make sure the index is created
        if ( isset($exists->error) 
            && $exists->error->type == 'index_not_found_exception' ) {
            $created = $this->_request('PUT', NULL, $this->index);
            if ( $created == false ) {
                return false;
            }

            //check the error status
            if ( isset($created->error) ) {
                return false;
            }

            //check the acknowledged status
            if ( ! isset($created->acknowledged) 
                || $created->acknowledged == false ) {
                return false;
            }
        }

        //---------------------------------------
        /*
         * 2, create the type mapping
         * convert the fields to elasticsearch DSL
        */
        $workload = $setting == NULL ? array() : $setting;
        $workload['properties'] = $this->fields;
        $DSL = json_encode($workload);
        $ret = $this->_request('PUT', $DSL, "{$this->index}/{$this->type}/_mapping");
        if ( $ret == false ) {
            return false;
        }

        if ( isset($ret->error) ) {
            throw new Exception("mapper exception: {$ret->error->reason}");
        }

        return $ret->acknowledged;
    }

    /**
     * parse the SQL-style query condition
     * We mean to make it Compatible with the SQL common model
     *
     * @Note: here we mean to make the elasticsearch works like a traditional
     *  SQL database, so we design it to use the elasticsearch filter always.
     *
     * 01, array(field => '=val')
     * 02, array(field => '>=|<=val')            //range query
     * 03, array(field => '!=val')
     * 04, array(field => 'in(v1,v2,...)')       //in search
     * 05, array(field => 'not in(v1,v2,...)')   //in search
     * 06, array(field => 'like %value%')        //like match
     *
     * @param   $_where
     * @return  array(Elasticsearch DSL)
    */
    protected function parseSQLCompatibleQuery($_where)
    {
        $query_or  = NULL;
        $query_and = NULL;
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
                    if ( $query_or == NULL ) $query_or = array();
                    $query_or[]  = $this->parseSQLCompatibleQuery($value);
                } else {
                    if ( $query_and == NULL ) $query_and = array();
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
                $limit = NULL;
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
                $limit = NULL;
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
        if ( $query_and == NULL && $query_or == NULL ) {
            return array(
                'bool' => $query
            );
        }

        //regroup the query
        //1, merge the query and the query_and branch
        if ( $query_and != NULL  ) {
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
        if ( $query_or != NULL ) {
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
     * parse the sql compatible syntax, orde, limit to form the final 
     *  elasticsearch query DSL
     *
     * @param   $_where
     * @param   $_order
     * @param   $_limit
     * @param   $_query
     * @return  String query DSL
    */
    protected function getQueryDSL($_filter, $_order, $_limit, $_query=NULL)
    {
        /*
         * filter condition parse
         * default to the sql compatible style syntax parser
         * and default to the match_all elasticsearch query for empty filter
         *  and if there is no complex fulltext query defined
        */
        $filter = NULL;
        if ( $_filter != NULL ) {
            $filter = $this->parseSQLCompatibleQuery($_filter);
        }

        /*
         * query check and define
        */
        $query = NULL;
        if ( $_query == NULL ) {
            $filter = array(
                'match_all' => array()
            );
        } else if ( isset($_query['field']) && isset($_query['query']) ) {
            $qdata = isset($_query['option']) ? $_query['option'] : array();
            $qdata['query'] = $_query['query'];
            switch ( $_query['type'] ) {
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
        $sort = NULL;
        if ( $_order != NULL ) {
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
        $boolQuery = array();
        if ( $query  != NULL ) $boolQuery['must']   = $query;
        if ( $filter != NULL ) $boolQuery['filter'] = $filter;
        $queryDSL = array(
            'query' => array('bool' => $boolQuery)
        );

        //check and define the query sort
        if ( $sort != NULL ) {
            $queryDSL['sort'] = $sort;
        }

        //check and define the from, size attributes
        if ( $from >= 0 ) $queryDSL['from'] = $from;
        if ( $size >  0 ) $queryDSL['size'] = $size;

        //check and define the highlight options
        //rewrite the user define and auto set the fields
        if ( isset($_query['highlight']) ) {
            $highlight = array(
                'tag_schema' => 'styled',
                'pre_tags'   => array('<b class="es-jcseg-highlight">'),
                'post_tags'  => array('</b>'),
                'order'      => 'score',
                'number_of_fragments' => 1,
                'fragment_size' => 86,
                'no_match_size' => 86,
                'type'   => 'fvh',
                'fields' => NULL
            );

            foreach ( $_query['highlight'] as $key => $val ) {
                $highlight[$key] = $val;
            }

            //check and pre-process the highlight fields
            if ( $highlight['fields'] == NULL ) {
                $fields = array();
                if ( is_string($_query['field']) ) {
                    $fields[$_query['field']] = array(
                        'boost' => 1
                    );
                } else {
                    foreach ( $_query['field'] as $field_name ) {
                        $field_name = preg_replace('/\^\d+/', '', $field_name);
                        $fields[$field_name] = array(
                            'boost' => 1
                        );
                    }
                }
                $highlight['fields'] = $fields;
            }

            $queryDSL['highlight'] = $highlight;
        }

        return self::array2Json($queryDSL);
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
         * query fields checking and pro-process
         * @Note: empty fields will cause global _source fetch
        */
        $field_string = NULL;
        if ( is_array($_fields) ) {
            $field_string = implode(',', $_fields);
        } else {
            $field_string = trim($_fields);
        }

        $args = '_source=true';
        if ( strlen($field_string) > 0 ) {
            $args = $field_string[0]=='*' ? NULL : "_source={$field_string}";
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
        if ( $srcMode == true ) {
            foreach ( $json['hits']['hits'] as $hit ) {
                $ret[] = $hit['_source'];
            }
        } else {
            $ret['took']  = $json['took'];
            $ret['total'] = $json['hits']['total'];
            $ret['data']  = array();
            foreach ( $json['hits']['hits'] as $hit ) {
                $ret['data'][] = array(
                    '_index' => $hit['_index'],
                    '_type'  => $hit['_type'],
                    '_id'    => $hit['_id'],
                    '_score' => $hit['_score']
                );
            }
        }

        return $ret;
    }

    /**
     * start the scroll request and return an iterator
     *
     * @param   $_where
     * @param   $ttl time to live in seconds
     * @return  Mixed(false or Array)
    */
    public function iterator($_fields, $_where, $_order, $size, $ttl)
    {
        /*
         * check and define the defaule sorting
         * @Note: (From official documentation)
         * Scroll requests have optimizations that make them faster when the sort order is _doc. 
         * If you want to iterate over all documents regardless of the order
        */
        if ( $_order == NULL ) $_order = array('_doc' => 'asc');

        $_src = $this->getQueryFieldArgs($_fields);
        $_DSL = $this->getQueryDSL($_where, $_order, array(-1, $size));
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_search", "scroll={$ttl}s&{$_src}", true);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_scroll_id": "a very long scroll id string",
         *  "took": 26,
         *  "timed_out": false,
         *  "_shards": {
         *      "total": 5,
         *      "successful": 5,
         *      "failed": 0
         *  },
         *  //including the hits for the first batch
         *  "hits": {
         *      "total": 0,
         *      "max_score": 0,
         *      "hits": []
         *  }
         * }
        */

        if ( ! isset($json['_scroll_id']) || ! isset($json['hits'])) {
            return false;
        }

        if ( empty($json['hits']['hits']) ) {
            return false;
        }

        $total   = $json['hits']['total'];
        $srcMode = $_fields===false ? false : true;
        $data    = $this->getQuerySets($json, $srcMode);

        return array(
            'ttl'       => $ttl,
            'scroll_id' => $json['_scroll_id'],
            'data'      => $data,
            'source'    => $srcMode,
            'size'      => $size,
            'total'     => $total,
            'counter'   => $srcMode ? count($data) : count($data['data']),
            'buffer'    => true
        );
    }

    /**
     * get the next batch of results of the specified iterator
     *
     * @param   $iterator
     * @return  Mixed(false or Array)
    */
    public function scroll(&$iterator)
    {
        if ( $iterator['buffer'] ) {
            $iterator['buffer'] = false;
            return $iterator['data'];
        }

        if ( $iterator['counter'] >= $iterator['total'] ) {
            return false;
        }

        $_DSL = self::array2Json(array(
            'scroll'    => "{$iterator['ttl']}s",
            'scroll_id' => $iterator['scroll_id']
        ));

        $json = $this->_request('POST', $_DSL, "_search/scroll",  NULL, true);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_scroll_id": "a very long scroll id string",
         *  "took": 8,
         *  "timed_out": false,
         *  "terminated_early": false,
         *  "_shards": {
         *      "total": 5,
         *      "successful": 5,
         *      "failed": 0
         *  },
         *  "hits": {
         *      "total": 37,
         *      "max_score": null,
         *      "hits": []
         *  }
         * }
        */

        if ( ! isset($json['_scroll_id']) || ! isset($json['hits']) ) {
            return false;
        }

        if ( empty($json['hits']['hits']) ) {
            return false;
        }

        $data = $this->getQuerySets($json, $iterator['source']);
        $iterator['counter'] += ($iterator['source'] ? count($data) : count($data['data']));
        //$iterator['data']    = $data;

        return $data;
    }


    /**
     * get the last active ElasticSearch model object
     *
     * @return  C_Model
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
        throw new Exception("Not implemented yet");
    }

    /**
     * get the total count
     *
     * @param   $_where
     * @param   $_group
     * @return  int
    */
    public function totals($_where=NULL, $_group=NULL)
    {
        $_DSL = $this->getQueryDSL($_where, NULL, NULL);
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_count");
        if ( $json == false ) {
            return 0;
        }

        /*
         * api return:
         * {
         *  "count": 8,
         *  "_shards": {
         *      "total": 5,
         *      "successful": 5,
         *      "failed": 0
         *  }
         * }
        */

        if ( ! isset($json->count) ) {
            return 0;
        }

        return $json->count;
    }

    /**
     * Get a vector from the specified source
     *
     * @param   $_fields    query fields array
     * @param   $_where
     * @param   $_order
     * @param   $_limit
     * @param   $_group
     * @return  Mixed(false or Array)
     * if the $_fields == false and the the statistics info 
     *  and the _index,_type,_id data sets will return
    */
    public function getList($_fields, $_where=NULL, $_order=NULL, $_limit=NULL, $_group=NULL)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $_DSL = $this->getQueryDSL($_where, $_order, $_limit);
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_search", $_src, true);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 3,
         *  "timed_out": false,
         *  "_shards": {
         *      "total": 5,
         *      "successful": 5,
         *      "failed": 0
         *  },
         *  "hits": {
         *      "total": 7,
         *      "max_score": null,
         *      "hits": [{
         *          "_index": "stream",
         *          "_type": "main",
         *          "_id": "141097",
         *          "_score": null,
         *          "_source": {
         *              "pubtime": 1461895200,
         *              "cate_id": 35,
         *              "user_id": 251360,
         *              "ack_code": "GJ7PZTIV"
         *          },
         *          "sort": [
         *              141097
         *          ]
         *      }]
         *  }
         * }
         */

        if ( ! isset($json['hits']) ) {
            return false;
        }

        if ( empty($json['hits']['hits']) ) {
            return false;
        }
 
        return $this->getQuerySets(
            $json, $_fields===false ? false : true
        );
    }

    /**
     * match query interface
     * do the elasticsearch complex score query like:
     * 1, match query query
     * 2, multi_match query
     * 3, query_string query
     *
     * @param   $_fields
     * @param   $_filter SQL compatible query filter
     * @param   $_query fulltext query
     * @param   $_order
     * @param   $_limit
     * @param   $_highlight
     * @return  Mixed(Array or false)
    */
    public function match($_fields, $_filter, $_query, $_order=NULL, $_limit=NULL)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $_DSL = $this->getQueryDSL($_filter, $_order, $_limit, $_query);
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_search", $_src, true);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 3,
         *  "timed_out": false,
         *  "_shards": {
         *      "total": 5,
         *      "successful": 5,
         *      "failed": 0
         *  },
         *  "hits": {
         *      "total": 7,
         *      "max_score": null,
         *      "hits": [{
         *          "_index": "stream",
         *          "_type": "main",
         *          "_id": "141097",
         *          "_score": null,
         *          "_source": {
         *              "pubtime": 1461895200,
         *              "cate_id": 35,
         *              "user_id": 251360,
         *              "ack_code": "GJ7PZTIV"
         *          },
         *          "highlight": {
         *                field1: []
         *                field2: []
         *           },
         *          "sort": [
         *              141097
         *          ]
         *      }]
         *  }
         * }
         */

        if ( ! isset($json['hits']) ) {
            return false;
        }

        if ( empty($json['hits']['hits']) ) {
            return false;
        }

        //----------------------------------------------------
        //pre-process the returning data

        $ret = array();
        if ( $_fields == false ) {
            $ret['took']  = $json['took'];
            $ret['total'] = $json['hits']['total'];
            $ret['data']  = array();
            foreach ( $json['hits']['hits'] as $hit ) {
                $ret['data'][] = array(
                    '_index' => $hit['_index'],
                    '_type'  => $hit['_type'],
                    '_id'    => $hit['_id'],
                    '_score' => $hit['_score']
                );
            }

            return $ret;
        }

        $ret['took']  = $json['took'];
        $ret['total'] = $json['hits']['total'];
        $ret['max_score'] = $json['hits']['max_score'];
        $ret['hits']  = $json['hits']['hits'];
        //foreach ( $json['hits']['hits'] as $hit ) {
        //    //$data[] = array(
        //    //    '_source'   => $hit['_source'],
        //    //    'highlight' => $hit['highlight']
        //    //);
        //    $ret['hits'][] = $hit;
        //}

        return $ret;
    }

    /**
     * get a specified record from the specified table
     *
     * @param   $Id
     * @param   $_fields
     * @see     #getList($_fields, $_where, $_order, $_limit, $_group)
    */
    public function get($_fields, $_where)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $_DSL = $this->getQueryDSL($_where, NULL, 1);
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_search", $_src, true);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 3,
         *  "timed_out": false,
         *  "_shards": {
         *      "total": 5,
         *      "successful": 5,
         *      "failed": 0
         *  },
         *  "hits": {
         *      "total": 7,
         *      "max_score": null,
         *      "hits": [{
         *          "_index": "stream",
         *          "_type": "main",
         *          "_id": "141097",
         *          "_score": null,
         *          "_source": {
         *              "pubtime": 1461895200,
         *              "cate_id": 35,
         *              "user_id": 251360,
         *              "ack_code": "GJ7PZTIV"
         *          },
         *          "sort": [
         *              141097
         *          ]
         *      }]
         *  }
         * }
         */

        if ( ! isset($json['hits']) || $json['hits']['total'] == 0 ) {
            return false;
        }

        if ( $_fields === false ) {
            $hit = $json['hits']['hits'][0];
            return array(
                '_index' => $hit['_index'],
                '_type'  => $hit['_type'],
                '_id'    => $hit['_id'],
                '_score' => $hit['_score']
            );
        }

        return $json['hits']['hits'][0]['_source'];
    }

    /**
     * get the specified record by primary key
     *
     * @param   $_fields
     * @param   $id
     * @return  Mixed(Array or false for failed)
    */
    public function getById($_fields, $id)
    {
        $_src = $this->getQueryFieldArgs($_fields);
        $json = $this->_request('GET', NULL, "{$this->index}/{$this->type}/{$id}", $_src, true);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_index": "stream",
         *  "_type": "main",
         *  "_id": "18032",
         *  "_version": 2,
         *  "found": true,
         *  "_source": {
         *      "title": "...",
         *      "cate_id": 35,
         *      "user_id": 7,
         *      "admin_id": 7,
         *      "ack_code": "BIHM5BbC"
         *  }
         * }
         *
        */

        if ( ! isset($json['found']) || $json['found'] == false ) {
            return false;
        }

        if ( $_fields === false ) {
            return array(
                '_index' => $json['_index'],
                '_type'  => $json['_type'],
                '_id'    => $json['_id'],
                '_score' => 0.0
            );
        }

        return $json['_source'];
    }

    //-------------------------------------------------------------------------

    /**
     * Add an new record to the data source
     * @Note: thanks god, elasticsearch will do the datatype conversion
     *
     * @param   $data
     * @param   $onDuplicateKey
     * @return  Mixed false or row_id
    */
    public function add($data, $onDuplicateKey=NULL)
    {
        /*
         * check or define the auto generated primary_key
        */
        $id = NULL;
        if ( $this->primary_key != NULL ) {
            if ( ! isset($data[$this->primary_key])) {
                throw new Exception("Missing mapping for {$this->primary_key} in the source data");
            }
            $id = $data[$this->primary_key];
        } else {
            $id = $this->genUUID($data);
        }

        //do the data types conversion
        $this->stdDataTypes($data);

        $_DSL = self::array2Json($data);
        $json = $this->_request('PUT', $_DSL, "{$this->index}/{$this->type}/{$id}");
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_index":"stream",
         *  "_type":"main",
         *  "_id":"5717659b01b9402816458d4a",
         *  "_version":1,
         *  "_shards":{
         *      "total":2,
         *      "successful":1,
         *      "failed":0
         *  },
         *  "created":true
         * }
        */

        return isset($json->_id) ? $json->_id : $id;
    }

    /**
     * batch add with no fragments support
     *
     * @param   $data
    */
    public function batchAdd($data, $onDuplicateKey=NULL)
    {
        $workload = array();
        foreach ( $data as $val ) {
            if ( $this->primary_key != NULL ) {
                if ( ! isset($val[$this->primary_key])) {
                    throw new Exception("Missing mapping for {$this->primary_key} in the source data");
                }
                $id = $val[$this->primary_key];
            } else {
                $id = $this->genUUID($val);
            }

            $workload[] = "{\"index\":{\"_index\":\"{$this->index}\",\"_type\":\"{$this->type}\",\"_id\":\"{$id}\"}}";

            //do the data types conversion
            $this->stdDataTypes($val);

            $workload[] = self::array2Json($val);
        }

        /*
         * @Note: append something at the last
         * so the elasticsearch could Recognize the last record
         * well it maybe a bug of elasticsearch, but we have to do it this way
        */
        $workload[] = "\n";

        $_DSL = implode("\n", $workload);
        $json = $this->_request('PUT', $_DSL, "{$this->index}/{$this->type}/_bulk");
        if ( $json == false || ! isset($json->items) ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "took": 124,
         *  "errors": false,
         *  "items": [{
         *      "index": {
         *      "_index": "stream",
         *      "_type": "main",
         *      "_id": "18064",
         *      "_version": 1,
         *      "_shards": {
         *          "total": 2,
         *          "successful": 1,
         *          "failed": 0
         *      },
         *      "status": 201
         *      }
         *  }]
         * }
        */

        $ok_count = 0;
        foreach ( $json->items as $item ) {
            if ( isset($item->index->_id) ) {
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
        if ( $_where == NULL ) {
            throw new Exception("Empty update condition is not allow");
        }

        /*
         * get the query iterator
        */
        $iterator = $this->iterator(
            false, $_where, array('_doc' => 'asc'), $this->updateTraffic, 2
        );
        if ( $iterator == false ) {
            return false;
        }

        //stdlize the original data types
        $this->stdDataTypes($data);
        $cData = self::array2Json(array('doc' => $data));

        $count = 0;
        while ( ($ret = $this->scroll($iterator)) != false ) {
            $workload = array();
            foreach ( $ret['data'] as $val ) {
                $workload[] = "{\"update\":{\"_index\":\"{$val['_index']}\",\"_type\":\"{$val['_type']}\",\"_id\":\"{$val['_id']}\"}}";
                $workload[] = $cData;
            }

            $workload[] = "\n";
            $_DSL = implode("\n", $workload);
            $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_bulk");
            if ( $json == false || ! isset($json->items) ) {
                return false;
            }

            /*
             * api return:
             * {
             *  "took": 124,
             *  "errors": false,
             *  "items": [{
             *      "update": {
             *      "_index": "stream",
             *      "_type": "main",
             *      "_id": "18064",
             *      "_version": 1,
             *      "_shards": {
             *          "total": 2,
             *          "successful": 1,
             *          "failed": 0
             *      },
             *      "status": 200
             *      }
             *  }]
             * }
             */

            foreach ( $json->items as $item ) {
                if ( isset($item->update->_id) ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * update by primary key
     * @Note: when you want to globally update the record and we suggest
     * you use this method to do it, if you just want to update the value of
     * a single field, and you should use #set|#setById method instead 
    */
    public function updateById($data, $id, $affected_rows=true)
    {
        //check and remove the primary key
        //if ( $this->primary_key != NULL 
        //    && isset($data[$this->primary_key]) ) {
        //    unset($data[$this->primary_key]);
        //}

        //do the data types conversion
        $this->stdDataTypes($data);

        /*
         * we got to use the update api for the elasticsearch
         * doc (instance of elasticsearch document) keywords used here
        */
        $_DSL = self::array2Json(array('doc' => $data));
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/{$id}/_update");
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_index":"stream",
         *  "_type":"main",
         *  "_id":"141097",
         *  "_version":2,
         *  "_shards":{
         *      "total":2,
         *      "successful":1,
         *      "failed":0
         *  },
         *  "created":false
         * }
        */

        return isset($json->_id) ? true : false;
    }

    /**
     * Set the value of the specified field of the specified reocords
     *  in data source
     *
     * @param   $_field
     * @param   $_val
     * @param   $_where
     * @param   $affected_rows
    */
    public function set($_field, $_val, $_where, $affected_rows=true)
    {
        $data = array($_field => $_val);
        return $this->update($data, $_where, $affected_rows);
    }

    //set by primary key
    public function setById($_field, $_val, $id, $affected_rows=true)
    {
        $data = array($_field => $_val);
        return $this->updateById($data, $id, $affected_rows);
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
    }

    /**
     * increase the value of the specified field by primary_key
     * 
     * @param   $_field
     * @param   $_offset
     * @param   $id
     * @Note    the fields_increase.groovy script must be compile
     *  and loaded by elasticsearch, recommend to use the elasticsearch-jcseg
    */
    public function increaseById($_field, $_offset, $id)
    {
        //pro-process the field
        $fields = array();
        $values = array();
        if ( is_array($_field) ) {
            foreach ( $_field as $key => $val ) {
                $fields[] = $key;
                $values[] = $val;
            }
        } else {
            $fields[] = $_field;
            $values[] = $_offset;
        }

        $workload = array();
        $workload['script'] = array(
            'file'   => 'fields_increase',
            'lang'   => 'groovy',
            'params' => array(
                'act'    => 'increase',
                'fields' => $fields,
                'values' => $values
            )
        );

        $_DSL = self::array2Json($workload);
        $args = array('retry_on_conflict' => 5);
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/{$id}/_update", $args);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_index":"stream",
         *  "_type":"main",
         *  "_id":"141097",
         *  "_version":2,
         *  "_shards":{
         *      "total":2,
         *      "successful":1,
         *      "failed":0
         *  }
         * }
        */

        return isset($json->_id) ? true : false;
    }

    /**
     * decrease the value of the specified field of the specified records
     *  in data source
     *
     * @param   $_field
     * @param   $_offset
     * @param   $_where
     * @return  Mixed
    */
    public function decrease($_field, $_offset, $_where)
    {
    }

    /**
     * decrease the value of the specified field by primary_key
     * 
     * @param   $_field
     * @param   $_offset
     * @param   $id
     * @Note    the fields_increase.groovy script must be compile
     *  and loaded by elasticsearch, recommend to use the elasticsearch-jcseg
    */
    public function decreaseById($_field, $_offset, $id)
    {
        //pro-process the field
        $fields = array();
        $values = array();
        if ( is_array($_field) ) {
            foreach ( $_field as $key => $val ) {
                $fields[] = $key;
                $values[] = $val;
            }
        } else {
            $fields[] = $_field;
            $values[] = $_offset;
        }

        $workload = array();
        $workload['script'] = array(
            'file'   => 'fields_increase',
            'lang'   => 'groovy',
            'params' => array(
                'act'    => 'decrease',
                'fields' => $fields,
                'values' => $values
            )
        );


        $_DSL = self::array2Json($workload);
        $args = array('retry_on_conflict' => 5);
        $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/{$id}/_update", $args);
        if ( $json == false ) {
            return false;
        }

        /*
         * api return:
         * {
         *  "_index":"stream",
         *  "_type":"main",
         *  "_id":"141097",
         *  "_version":2,
         *  "_shards":{
         *      "total":2,
         *      "successful":1,
         *      "failed":0
         *  }
         * }
        */

        return isset($json->_id) ? true : false;
    }

    /**
     * Delete the specified records
     * @Note: query delete is not support by default for elasticsearch.
     * 1, query the first {$this->deleteTraffic} records and get the totals records and 
     *  send the batch delete DSL to the _bulk terminal
     * 2, if the totals records is more than {$this->deleteTraffic}, continue the above work
     *  until all the records is deleted
     *
     * @param   $_where
     * @param   $frag_recur
     * @param   Mixed(false or affected rows)
    */
    public function delete($_where, $frag_recur=true)
    {
        if ( $_where == NULL ) {
            throw new Exception("Empty delete condition is not allow");
        }

        /*
         * get the query iterator
        */
        $iterator = $this->iterator(
            false, $_where, array('_doc'=>'asc'), $this->deleteTraffic, 2
        );
        if ( $iterator == false ) {
            return false;
        }

        $count  = 0;
        while ( ($ret = $this->scroll($iterator)) != false ) {
            //build the batch workload
            $workload = array();
            foreach ( $ret['data'] as $val ) {
                $workload[] = "{\"delete\":{\"_index\":\"{$val['_index']}\",\"_type\":\"{$val['_type']}\",\"_id\":\"{$val['_id']}\"}}";
            }

            $workload[] = "\n";
            $_DSL = implode("\n", $workload);
            $json = $this->_request('POST', $_DSL, "{$this->index}/{$this->type}/_bulk");
            if ( $json == false || ! isset($json->items) ) {
                return false;
            }

            /*
             * api return:
             * {
             *  "took": 71,
             *  "errors": false,
             *  "items": [{
             *      "delete": {
             *          "_index": "stream",
             *          "_type": "main",
             *          "_id": "17895",
             *          "_version": 2,
             *          "_shards": {
             *              "total": 2,
             *              "successful": 1,
             *              "failed": 0
             *          },
             *          "status": 200,
             *          "found": true
             *      }
             *  }]
             * }
            */ 
            foreach ( $json->items as $item ) {
                if ( isset($item->delete) && $item->delete->found ) {
                    $count++;
                }
            }
        }

        return $count;
    }

    //delete by primary key
    //@frament suports
    public function deleteById($id)
    {
        $json = $this->_request('DELETE', NULL, "{$this->index}/{$this->type}/{$id}");
        if ( $json == false ) {
            return false;
        }

        /*
         * api return: 
         * {
         *  "found":true,
         *  "_index":
         *  "stream",
         *  "_type":
         *  "main",
         *  "_id":"141090",
         *  "_version":7,
         *  "_shards":{
         *      "total":2,
         *      "successful":1,
         *      "failed":0
         *   }
         * }
        */

        if ( isset($json->found) ) {
            return $json->found;
        }

        return isset($json->_id) ? true : false;
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
     * data types standardization
     * @Note: make sure this->fields is not NULL before you invoke this method
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
     * @param   $type
     * @param   $id
     * @return  Mixed(Object or false)
    */
    protected function _request( $method, $dsl, $uri, $args=NULL, $_assoc=false)
    {
        if ( ! isset(self::$methods[$method]) ) {
            throw new Exception("Unknow http request method {$method}");
        }

        $_header = array(
            'User-Agent: elasticsearch php client'
        );

        $qstring = NULL;
        $baseUrl = "{$this->baseUrl}/{$uri}";
        if ( $args != NULL ) {
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
        curl_setopt($curl, CURLOPT_HTTPHEADER, $_header);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        if ( $dsl != NULL ) {
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
        if ( $json == NULL ) {
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
     * initialize the current model from the specified mapping
     * configuration that defined in conf/db/hosts.conf.php
     *
     * @param   $section
     * @return  ElasticSearchModel
    */
    protected function _InitFromConfigByKey($section)
    {
        //Load the elasticsearch config
        $conf = config("database#{$section}");
        if ( $conf == false ) {
            throw new Exception("Invalid db section {$section}");    
        }

        $this->index   = $conf['index'];
        $this->baseUrl = "http://{$conf['host']}:{$conf['port']}";

        return $this;
    }

    /**
     * json encoder: convert the array to json string
     * and the original string will not be encoded like the json_encode do
     *
     * @param   $data
     * @return  String
    */
    public static function array2Json($data)
    {
        if ( ! is_array($data) ) {
            $type = gettype($data);
            switch ( $type[0] ) {
            case 'b':
                return $data ? 'true' : 'false';
            case 'i':
            case 'd':
            case 'N':
                return $data;
            case 's':
                return '"'.self::addslash($data).'"';
            default:
                return NULL;
            }
        }

        //define the associative attribute
        $isAssoc = false;
        foreach ( $data as $key => $val ) {
            if ( is_string($key) ) {
                $isAssoc = true;
                break;
            }
        }

        $buff = [];
        foreach ( $data as $key => $val ) {
            $type = gettype($val);
            switch ( $type[0] ) {
            case 'o':   //object
            case 'r':   //resource
                continue;
                break;
            case 'b':   //boolean
                $val = $val ? 'true' : 'false';
                break;
            case 'i':   //integer
            case 'd':   //double
            case 'N':   //NULL
                //leave it unchange
                break;
            case 's':
                $val = '"'.self::addslash($val, '"').'"';
                break;
            case 'a':
                $val = self::array2Json($val);
                break;
            }

            //check and append the key
            if ( $isAssoc ) {
                $buff[] = "\"{$key}\":{$val}";
            } else {
                $buff[] = $val;
            }
        }

        if ( $isAssoc ) {
            $json = '{'.implode(',', $buff).'}';
        } else {
            $json = '['.implode(',', $buff).']';
        }

        return $json;
    }

    /**
     * string slash function, slash the specified sub-string
     *
     * @param   $str
     * @return  String
    */
    public static function addslash($str, $tchar)
    {
        $sIdx = strpos($str, $tchar);
        if ( $sIdx === false ) {
            return $str;
        }

        $buff   = [];
        $buff[] = substr($str, 0, $sIdx);
        if ( $str[$sIdx-1] != '\\' ) {
            $buff[] = '\\';
        }
        $buff[] = '"';
        $sIdx++;

        while (($eIdx = strpos($str, $tchar, $sIdx)) !== false) {
            $buff[] = substr($str, $sIdx, $eIdx-$sIdx);
            if ( $str[$eIdx-1] != '\\' ) {
                $buff[] = '\\';
            }
            $buff[] = '"';

            $sIdx = $eIdx + 1;
        }

        //check and append the end part
        if ( $sIdx < strlen($str) ) {
            $buff[] = substr($str, $sIdx);
        }

        return implode('', $buff);
    }

}
?>
