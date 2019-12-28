<?php
/**
 * mysql class offer common interface to handling
 *         the database operation. <br />
 *
 * @author    chenxin <chenxin619315@gmail.com>
 * @version    15.03.30
 */

 //----------------------------------------------------------
class Mysql implements Idb
{
    private $_link              = NULL;         //mysql connection resource
    private $rlink              = NULL;         //mysql read connection resource
    private $clink              = NULL;         //last used connection resource

    private $_host              = NULL;         //connection information
    private $_debug             = false;        //open the debug mode ?
    private $_srw               = false;        //separate the read/write operation ?
    private $_rsidx             = 0;            //read server index
    private $_hostCache         = array();      //connect host cache
    private $_escape            = true;
    private $_reconnectTimeout  = 30;           //re-connect wait seconds
    
    public function __construct( &$_host )
    {
        $this->_host = &$_host;
        
        //check the default magic quotes for GPC data
        $this->_escape = get_magic_quotes_gpc();
    }

    /*
     * connected to the database server and do some query work to unify the charset
     *
     * @param   $conf standart syrian database connection conf
     * @param   $retry retry count
     * @return  resource database connection resource
     */
    private static function connect( &$conf, $retry = 0 )
    {
        $_link = mysqli_connect($conf['host'], $conf['user'], $conf['pass'], $conf['db'], $conf['port']);
        while ( $_link == FALSE && $retry > 0 ) {
            //sleep for 1 sec
            usleep(1000000);
            $_link = mysqli_connect($conf['host'], $conf['user'], $conf['pass'], $conf['db'], $conf['port']);
            $retry--;
        }

        if ( $_link == FALSE ) {
            throw new Exception("Unable to connect to the database server.\n");
        }
        
        $_charset = $conf['charset'];
        mysqli_query( $_link, 'SET NAMES \''.$_charset.'\'');
        mysqli_query( $_link, 'SET CHARACTER_SET_CLIENT = \''.$_charset.'\'');
        mysqli_query( $_link, 'SET CHARACTER_SET_RESULTS = \''.$_charset.'\'');

        return $_link;
    }
    
    /**
     * send an query string to the mysql server
     * @Note: use $_srw argument instead of $this->_srw to make the single
     *  specifield method read/write separate define available ...
     * 
     * @param   $_query query string
     * @param   $opt    could be read or write operation
     * @param   $_srw   start the read write separate ?
     * @return  mixed
     */
    private function query( &$_query, $opt, $_srw )
    {
        //connect to the database server as necessary
        $_sidx = 0;
        if ( $_srw == false || $opt == Idb::WRITE_OPT ) {
            if ( $this->_link == NULL ) {
                $conf   = isset($this->_host['__w']) ? $this->_host['__w'] : $this->_host;
                $this->_hostCache[0] = $conf;
                $this->_link = self::connect($conf);
            }
            $_sidx = 0;
            $this->clink = $this->_link;
        } else {
            //@Note: added at 2015-04-01
            //  for model separateed read and write but without
            //  a standart read and write db connection configuration ...
            if ( isset($this->_host['__r']) ) {
                if ( $this->rlink == NULL ) {
                    $conf   = $this->_host['__r'][$this->_rsidx];
                    $this->_hostCache[1] = $conf;
                    $this->rlink = self::connect($conf);
                }
                $_sidx = 1;
                $this->clink = $this->rlink;
            } else {
                if ( $this->_link == NULL ) {
                    $this->_hostCache[2] = $this->_host;
                    $this->_link = self::connect($this->_host);
                }
                $_sidx = 2;
                $this->clink = $this->_link;
            }
        }

        //print the query string for debug    
        if ( $this->_debug !== false ) {
            if ( is_string($this->_debug) ) {
                call_user_func(
                    $this->_debug, 
                    ($_sidx === 0 ? 'Master' : 'Slave') . "#query: {$_query}");
            } else {
                echo ($_sidx === 0 ? 'Master' : 'Slave') . "#query: {$_query}" ;
            }
        }

        /**
         * record the query result and do the re-execute, re-conenct
         *  if we lost connection with mysql.
         * @Note: for cli mode only
         *
         * error code:
         * 2006: lost connect with mysql (invalid session)
        */
        $query_ret = mysqli_query($this->clink, $_query);
        if ( $query_ret === false 
            && SR_CLI_MODE == true && mysqli_errno($this->clink) == 2006 ) {
            switch ( $_sidx ) {
            case 0:
            case 2:
                $this->_link = NULL;
                $this->_link = self::connect($this->_hostCache[$_sidx], $this->_reconnectTimeout);
                $this->clink = $this->_link;
                break; 
            case 1:
                $this->rlink = NULL;
                $this->rlink = self::connect($this->_hostCache[$_sidx], $this->_reconnectTimeout);
                $this->clink = $this->rlink;
                break;
            }

            //so, do it again
            $query_ret = mysqli_query($this->clink, $_query);
        }

        return $query_ret;
    }
    
    /**
     * Send the specifield sql string to the server
     *     and return the executed result as it is
     *
     * @param   $_sql
     * @param   $opt    operation const
     * @param   $_row   return the affected rows ? 
     * @param   $srw    separate read/write
     * @return  Mixed
    */
    public function execute( $_sql, $opt, $_row = false, $srw = NULL )
    {
        $ret = $this->query( $_sql, $opt, $srw===NULL ? $this->_srw : $srw );
        return ($_row) ? mysqli_affected_rows($this->clink) : $ret;
    }
    
    /**
     * insert the specified array into the database
     * 
     * @param    $_table
     * @param    $_array
     * @param    $onDuplicateKey
     * @param    $affected_rows
     * @return    mixed
     */
    public function insert( $_table, &$_array, $onDuplicateKey=NULL, $affected_rows=false )
    {
        $fields = array();
        $values = array();
        foreach ( $_array as $key => $val ) {
            $fields[] = $key;
            $values[] = $this->_escape ? "'{$val}'" : '\''.addslashes($val).'\'';
        }

        //check and append the on udplicate key handler
        if ( $onDuplicateKey != NULL ) {
            $onDuplicateKey = " ON DUPLICATE KEY {$onDuplicateKey}";
        }
        
        if ( ! empty($fields) ) {
            $_query = 'INSERT INTO ' . $_table . '(' . implode(',', $fields) .') VALUES(' . implode(',', $values) . ')'. $onDuplicateKey;
            if ( $this->query( $_query, Idb::WRITE_OPT, false ) != FALSE ) {
                return $affected_rows ? 1 : mysqli_insert_id( $this->clink );
            }
        }
        
        return FALSE;
    }

    /**
     * batch insert a data sets to the specifiel table
     *
     * @param   $_table
     * @param   $_array
     * @param   $onDuplicateKey
     * @return  mixed
     */
    public function batchInsert($_table, &$_array, $onDuplicateKey=NULL)
    {
        //format the fields
        $fields    = array();
        foreach ( $_array[0] as $key => $val ) {
            $fields[] = $key;
        }
        
        //format the data
        $values    = array();
        foreach ( $_array as $record ) {
            $item    = array();
            foreach ( $record as $key => $val ) {
                $item[] = $this->_escape ? "'{$val}'" : '\''.addslashes($val).'\'';
            }

            $values[] = '(' . implode(',', $item) . ')';
        }

        //check and append the on udplicate key handler
        if ( $onDuplicateKey != NULL ) {
            $onDuplicateKey = " ON DUPLICATE KEY {$onDuplicateKey}";
        }
        
        if ( ! empty( $fields ) ) {
            $_query = 'INSERT INTO ' . $_table . '(' . implode(',', $fields) . ') VALUES' . implode(',', $values) . $onDuplicateKey;
            if ( $this->query( $_query, Idb::WRITE_OPT, false ) != FALSE ) {
                //return mysqli_insert_id( $this->clink );
                return mysqli_affected_rows( $this->clink );
            }
        }
        
        return FALSE;
    }
    
    /**
     * delete the specified record
     * 
     * @param   $_table
     * @param   $_where
     * @param   $affected_rows return the affected rows?
     * @return  bool
     */
    public function delete( $_table, $_where, $affected_rows=true )
    {
        //for safe, where condition must needed
        $_query = 'DELETE FROM ' . $_table . ' WHERE '.$_where;
        if ( $this->query( $_query, Idb::WRITE_OPT, false ) != FALSE ) {
            return $affected_rows ? mysqli_affected_rows($this->clink) : true;
        }

        return FALSE;
    }
    
    
    /**
     * get a array list from the database
     * 
     * @param    $_query
     * @param    $_type
     * @param    $srw    ? separate read/write
     * @return    mixed
     */
    public function getList( $_query, $_type = MYSQLI_ASSOC, $srw = NULL )
    {
        $_ret = $this->query( $_query, Idb::READ_OPT, $srw===NULL ? $this->_srw : $srw );
        if ( $_ret !== FALSE ) {
            $_rows = array();
            while ( ( $_row = mysqli_fetch_array( $_ret, $_type ) ) != FALSE ) {
                $_rows[] = $_row;
            }
            return $_rows;
        }
        
        return FALSE;
    }
    
    /**
     * update the specified records
     *  all the value will be quoted with ' punctuation for default, So
     *  you don't have to care about the data type of the fields
     *  in you aim database table, Or make $_quote false...
     * 
     * @param   $_table
     * @param   $_array
     * @param   $_where
     * @param   $slashes
     * @para    $affected_rows return the affected rows?
     * @return  mixed
     */
    public function update( $_table, &$_array, $_where, $slashes=true, $affected_rows=true )
    {
        $gqstr  = ($slashes) ? '\'' : "";
        $values = array();
        foreach ( $_array as $key => $val ) {
            $qstr = $gqstr;
            $rval = NULL;

            /**
             * field value quote control
             * more control can be implemented here!
            */
            if ( is_array($val) ) {
                $rval = $val['value'];
                if ( isset($val['quote']) 
                    && $val['quote'] == false ) {
                    $qstr = "";
                }
            } else {
                $rval = $val;
            }

            $values[] = "{$key}={$qstr}" . ($slashes ? addslashes($rval) : $rval) . $qstr;
        }
        
        if ( ! empty($values) ) {
            $_sql = 'UPDATE ' . $_table . ' SET ' . implode(',', $values) . ' WHERE '.$_where;
            if ( $this->query( $_sql, Idb::WRITE_OPT, false ) == FALSE ) {
                return FALSE;
            }

            /*
             * return the query result directly or return the affected rows
            */
            return $affected_rows ? mysqli_affected_rows($this->clink) : true;
        }
        
        return FALSE;
    }
    
    /**
     * get the specified record
     *
     * @param   $_query
     * @param   $_type
     * @param   $srw    separate read/write
     * @return  mixed
     */    
    public function getOneRow( $_query, $_type = MYSQLI_ASSOC, $srw = NULL )
    {
        $_ret = $this->query( $_query, Idb::READ_OPT, $srw===NULL ? $this->_srw : $srw );
        if ( $_ret != FALSE ) return mysqli_fetch_array( $_ret, $_type );
        return FALSE;
    }
    
    /**
     * get row number of the specified query
     * 
     * @param   $_query
     * @param   $_res
     * @param   $srw    separate read/write
     * @return  int
     */
    public function getRowNum( $_query, $_res = false, $srw = NULL )
    {
        if ( $_res ) $_ret = $_res;
        else $_ret = $this->query( $_query, Idb::READ_OPT, $srw===NULL ? $this->_srw : $srw );
        if ($_ret != FALSE) return mysqli_num_rows($_ret);
        return 0;
    }
    
    /**
     * count the total records
     *
     * @param   $_table
     * @param   $_fields
     * @param   $_where
     * @param   $_group
     * @param   $srw    separate read/write
     * @return  int
    */
    public function count( $_table, $_field = 0, $_where = NULL, $_group = NULL, $srw = NULL )
    {
        $_query = 'SELECT count(' . $_field . ') FROM ' . $_table;
        if ( $_where != NULL ) $_query .= ' WHERE ' . $_where;
        if ( $_group != NULL ) $_query .= ' GROUP BY '. $_group;
        if ( ($_ret = $this->getOneRow($_query, MYSQLI_NUM, $srw)) != FALSE )
            return intval($_ret[0]);
        return 0;
    }

    /**
     * set the debug status
     *
     * @param    $_debug
     * @return    $this
     */
    public function setDebug($_debug)
    {
        $this->_debug = $_debug;
        return $this;
    }

    /**
     * set the read/write operation separate status
     *
     * @param    $_srw
     * @return    $this
     */
    public function setSepRW( $srw )
    {
        $this->_srw = $srw;
        return $this;
    }

    /**
     * slave server select stratety
     *     offer a factor to generate a better strategy
     *
     * @param    $factor    read server index selector factor
     * @return    $this
     */
    public function slaveStrategy($factor)
    {
        if ( isset($this->_host['__r']) ) {
            $this->_rsidx = $factor % count($this->_host['__r']);
        }

        return $this;
    }

    /**
     * get the last insert id
     *
     * @return    false || Integer
    */
    public function getLastInsertId()
    {
        return mysqli_insert_id($this->clink);
    }

    /**
     * get the affected rows
     *
     * @return    false || Integer
    */
    public function getAffectedRows()
    {
        return mysqli_affected_rows($this->clink);
    }

    /**
     * return the last error message
     *
     * @return  string
    */
    public function getLastError()
    {
        return mysqli_error($this->clink);
    }

    /**
     * return the last error code
     *
     * @return  int
    */
    public function getLastErrno()
    {
        return mysqli_errno($this->clink);
    }

    /**
     * release the mysql connection
    */
    public function release()
    {
        if ( $this->_link != NULL ) {
            mysqli_close( $this->_link );
            $this->_link = NULL;
        }

        if ( $this->rlink != NULL ) {
            mysqli_close( $this->rlink );
            $this->rlink = NULL;
        }
    }

    public function getSerial()
    {
        $serial = NULL;
        if ( isset($this->_host['serial']) ) {
            $serial = $this->_host['serial'];
        } else if ( isset($this->_host['db']) ) {
            $serial = $this->_host['db'];
        }

        return $serial;
    }

    /**
     * set reconnect timeout in seconds
     *
     * @param   $timeout
    */
    public function setReconnectTimeout($timeout)
    {
        $this->_reconnectTimeout = $timeout;
    }
    
    public function __destruct()
    {
        $this->release();
    }
}
?>
