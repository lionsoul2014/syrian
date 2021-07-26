<?php
/**
 * session 2.0 base abstract implementation.
 * 1, based on sign session id.
 * 2, mutl clients and limits supports.
 * 3, operation process safe based on driver CAS operation.
 * 4, not thread-safe.
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
abstract class SessionBase
{
    /* error constants for #start */
    const OK = 0x00;
    const EMPTY_SESS_ID  = 0x01;
    const DECODE_FAILED  = 0x02;
    const FIELD_MISSING  = 0x03;
    const INVALID_SIGN   = 0x04;
    const INVALID_SEED   = 0x05;
    const INVALID_ADDR   = 0x06;
    const CLIENT_MISSING = 0x07;

    const CAS_FAILED = 100;  # operation failed cus of CAS failed.
    const OPT_FAILED = 101;  # the other operation failed

    const STATUS_RM = -1;
    const STATUS_OK = 1;

    const FIELD_CLIENT = '__clients';   # client list
    const FIELD_RG = 'rg';              # register time
    const FIELD_AR = 'ar';              # address
    const FIELD_AT = 'at';              # last access time
    const FIELD_CT = 'ct';              # counter
    const FIELD_ST = 'st';              # status
    const FIELD_UA = 'ua';              # user-agent


    /* empty session data structure */
    private static $_empty_sess_data = [
        self::FIELD_CLIENT => array()
    ];

    /* session global vars */
    protected $_sess_data = null;
    protected $_sess_name = null;
    protected $_sess_id   = null;
    protected $_sess_uid  = null;
    protected $_sess_seed = null;

    /* common config item */
    protected $_ttl = 1800;             # default expire time to 30 mins
    protected $_cookie_domain = '';
    protected $_create_new = false;     # the first time create the session ?
    protected $_need_flush = false;     # default flush mark to false
    protected $_max_retries = 3;

    /* CAS operation token*/
    protected $_cas_token  = null;

    /* is the data row exists in the driver
     * this should be defined after calling the #_read implementation. */
    protected $_row_exists = true;

    /* maximum parallel clients, -1 for not limits 
     * @Note: set this before calling #start() */
    protected $_max_clients = -1;


    /**
     * base config sample:
     * ```
     * $_conf = array(
     *   'ttl'             => 1800,             // time to live
     *   'session_name'    => 'SR_SESSID',      // session name
     *   'domain_strategy' => 'all_sub_host'    // domain strategy cur_host | all_sub_host
     *   'max_clients'     => 2                 // max clients
     * ); 
     * ``` */
    public function __construct($conf)
    {
        if (!isset($conf['session_name'])) {
            throw new Exception("Missing session_name config item");
        }

        $this->_sess_name = $conf['session_name'];
        if (isset($conf['ttl'])) {
            $this->_ttl = $conf['ttl'];
        }

        if (isset($conf['max_clients'])) {
            $this->_max_clients = $conf['max_clients'];
        }

        # check and get the cookie domain name
        if (isset($conf['cookie_domain'])) {
            $this->_cookie_domain = $conf['cookie_domain'];
        } else if (isset($conf['domain_strategy']) == false) {
            $this->_cookie_domain = $_SERVER['HTTP_HOST'] ?? '';
        } else if ($conf['domain_strategy'] == 'cur_host') {
            $this->_cookie_domain = $_SERVER['HTTP_HOST'] ?? '';
        } else if ($conf['domain_strategy'] == 'all_sub_host') {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $pnum = 0;
            $hostLen = min(strlen($host), 255);
            for ($i = 0; $i < $hostLen; $i++) {
                if ($host[$i] == '.') $pnum++;
            }
            
            // define the sub host ($pnum could be 0 like localhost)
            if ($pnum == 0) $this->_cookie_domain = $host;
            else if ($pnum == 1) $this->_cookie_domain = ".{$host}";
            else if ($pnum == 4 && preg_match(
                '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $host) == 1) {
                # ip address checking
                $this->_cookie_domain = $host;
            } else {
                $this->_cookie_domain = substr($host, strpos($host, '.'));
            }
        }

        # default the clients to an empty array
        $this->_sess_data = self::$_empty_sess_data;
    }

    /* 
     * start the session with the specified uid .
     *
     * data source: this session implementation will try to get the session id from
     * the http GET/POST request and then then COOKIE with the specified
     * session name as key.
     * 
     * session id: the session is a json pack with an optional uid attached,
     * and this uid should be unique for your system. we will try to generate
     * an unique uid if not specified.
     *
     * uid: uid should be use to for final data access and it should be the one
     *  in your database or somewhere you could find it again.
     *
     * @param   $create_new force to create a new session id ?
     * @param   $uid optional
     * @param   $errno
     * @return  bool
    */
    public function start($create_new=false, $uid=null, &$errno=self::OK)
    {
        # necessary resets the need flush mark
        $this->_need_flush = false;
        $this->_row_exists = true;

        # check and create the session
        if ($create_new == true) {
            import('Util');
            $seed = sprintf("%.6f", microtime(true));
            $addr = Util::getIpAddress(true);
            # check and generate the uid
            if ($uid == null) {
                $uid = build_signature(array(
                    '_sess_uid', $seed, $addr, mt_rand(0,13333), mt_rand(0, 13331)
                ));
            }

            $this->_cas_token = null;
            $this->_sess_uid  = $uid;
            $this->_sess_seed = $seed;
            $this->_sess_id   = base64_encode(json_encode(array(
                'uid'  => $this->_sess_uid,
                'seed' => $seed,
                'addr' => $addr,
                'sign' => build_signature(array('_sess_id', $this->_sess_uid, $seed, $addr))
            )));

            # track the seed for the newly created session pack
            $c_time = time();
            $this->_create_new = true;
            $this->_sess_data[self::FIELD_CLIENT] = array(
                $seed => array(
                    self::FIELD_AR => $addr,
                    self::FIELD_RG => $c_time,
                    self::FIELD_AT => $c_time,
                    self::FIELD_CT => 0,
                    self::FIELD_ST => self::STATUS_OK,
                    self::FIELD_UA => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                )
            );

            # try to reload and merge the data
            # clients max number checking for newly created session
            # if reach the limits we will delete the furthest access clients.
            $this->reload();
        } else {
            // try to get the session id
            if ($this->_sess_id != null) {
                # initialize by #setId
            } else if (isset($_COOKIE[$this->_sess_name]) 
                && strlen($_COOKIE[$this->_sess_name]) > 2) {
                # check session id from the cookie first
                $this->_sess_id = $_COOKIE[$this->_sess_name];
            } else if (isset($_REQUEST[$this->_sess_name]) 
                && strlen($_REQUEST[$this->_sess_name]) > 2) {
                # check session id from GET/POST
                $this->_sess_id = urldecode($_REQUEST[$this->_sess_name]);
            } else {
                $errno = self::EMPTY_SESS_ID;
                return false;
            }

            // check and do session id signature checking
            # 1, data decode
            if (($idval = base64_decode($this->_sess_id)) === false 
                || ($obj = json_decode($idval, false)) == null) {
                $this->destroy();   # destroy the error session
                $errno = self::DECODE_FAILED;
                return false;
            }

            # 2, fields structure checking
            unset($idval);
            foreach (array('uid', 'seed', 'addr', 'sign') as $f) {
                if (isset($obj->{$f}) == false) {
                    $this->destroy();   # destroy the error session
                    $errno = self::FIELD_MISSING;
                    return false;
                }
            }

            # 3, sign checking
            if (strcmp($obj->sign, build_signature(array(
                '_sess_id', $obj->uid, $obj->seed, $obj->addr))) != 0) {
                $this->destroy();   # destroy the error session
                $errno = self::INVALID_SIGN;
                return false;
            }

            # 4, basic initialize and load the data from the driver
            $this->cas_token   = null;
            $this->_sess_uid   = $obj->uid;
            $this->_sess_seed  = $obj->seed;
            $this->_create_new = false;
            $this->reload();
        }

        # client field checking
        if (!isset($this->_sess_data[self::FIELD_CLIENT])) {
            $errno = self::CLIENT_MISSING;
            return false;
        }

        # client seed & status checking
        if ($this->getClientItem(self::FIELD_ST) !== self::STATUS_OK) {
            $errno = self::INVALID_SEED;
            return false;
        }

        // request updates for current client
        $errno = self::OK;
        $this->setClientItem(self::FIELD_AT, time());
        $this->incClientItem(self::FIELD_CT, 1);
        # $this->set('at', microtime(true));

        // open the flush mark ONLY if everything are Ok
        $this->_need_flush = true;

        // check and update the session cookie
        // print("{$this->_sess_name}, {$this->_sess_id}, {$this->_sess_uid}");
        header("Session-Name: {$this->_sess_name}");
        header("Session-Id: {$this->_sess_id}");
        setcookie(
            $this->_sess_name, 
            $this->_sess_id, 
            time() + $this->_ttl, 
            '/', 
            $this->_cookie_domain, 
            false, true
        );

        return true;
    }

    /* public method to load the data from the driver */
    public function reload()
    {
        if ($this->_sess_uid == null) {
            return true;
        }

        $_data_str = $this->_read($this->_sess_uid, 
            $this->_cas_token, $this->_row_exists);
        if ($this->_row_exists == false) {
            // use an empty array instead if row not exists:
            // 1, first start the session
            // 2, the data were removed from the driver
            // to force to remove the current none-newly-register clients.
            $data = self::$_empty_sess_data;
        } else if (strlen($_data_str) < 3 
            || ($data = json_decode($_data_str, true)) == null) {
            return false;
        }

        // do the data none-clients fields merge.
        // CAS failed may need to calling reload multi-times, so:
        // 1. merge all the keys from both global _sess_data and local data.
        // 2. local data priority for conflicting fields.
        foreach ($data as $f => $val) {
            if ($f == self::FIELD_CLIENT) {
                continue;
            }

            # conflicting & missing fields
            $this->_sess_data[$f] = $val;
        }

        // check and apply the session log.
        foreach ($this->_sess_log as $f => $log) {
            if (isset($log[self::LOG_DEL])) {
                unset($this->_sess_data[$f]);
            } else if (isset($log[self::LOG_SET])) {
                $this->_sess_data[$f] = $log[self::LOG_SET];
            } else if (isset($log[self::LOG_INC])) {
                // $this->_sess_data[$f] = $log[self::LOG_INC];
            }
        }

        // do the clients merge.
        // 1, take the newly loaded data priority.
        // 2, missing fields in the newly loaded data consider
        //  to be removed by the last flush request.
        // 3, missing fields in the current _sess_data ? well, that should
        //  be a new client registered and just take it in.
        $_new_clients = $data[self::FIELD_CLIENT];
        $_cur_clients = &$this->_sess_data[self::FIELD_CLIENT];
        foreach ($_cur_clients as $k => $v) {
            if (isset($_new_clients[$k])) {
                // both own the client:
                if ($k == $this->_sess_seed) {
                    // keep the $_cur_clients[$k] priority
                } else {
                    // newly loaded data priority but keep the current status.
                    $_cur_clients[$k] = $_new_clients[$k];
                    $_cur_clients[$k][self::FIELD_ST] = $v[self::FIELD_ST];
                }
            } else if ($k == $this->_sess_seed 
                && $this->_create_new == true) {
                // force keep the current newly register client.
            } else {
                // missing field in the newly loaded data
                // consider the client were removed by another client.
                // so mark the current client as removed.
                $_cur_clients[self::FIELD_ST] = self::STATUS_RM;
            }
        }

        // append the newly registered clients by the other process
        foreach ($_new_clients as $k => $v) {
            if (isset($_cur_clients[$k])) {
                // do nothing here since both own client alreay merged
            } else {
                // just append the newly registered client
                $_cur_clients[$k] = $v;
            }
        }

        # print("#_reload: ");
        # print_r($this->_sess_data);

        return true;
    }

    /* get the final data for driver updates 
     * 1, mainly check and clean up the removed clients. 
     * 2, do the _max_clients clients check and clean up. */
    private function _getDataForFlush()
    {
        // @Note: make a copy of the global session data
        // since we cannot broke the original structures like
        //   marked at remove clients.
        $_sess_data = $this->_sess_data;

        // clean up the removed clients
        // check and clean up the expired clients 
        //  and that could be a case.
        $c_time = time();
        foreach ($_sess_data[self::FIELD_CLIENT] as $seed => $conf) {
            if ($conf[self::FIELD_ST] == self::STATUS_RM) {
                unset($_sess_data[self::FIELD_CLIENT][$seed]);
            } else if ($c_time - $conf[self::FIELD_AT] > $this->_ttl) {
                unset($_sess_data[self::FIELD_CLIENT][$seed]);
            }
        } 

        // check and do the furthest access clients clean up as need.
        // allow this->_max_clients clients register at the same time.
        if ($this->_max_clients > 0) {
            $_client_list = &$_sess_data[self::FIELD_CLIENT];
            $_client_size = count($_client_list);
            for ($i = $this->_max_clients; $i < $_client_size; $i++) {
                $seed = null;
                $time = $c_time;
                foreach ($_client_list as $s => $c) {
                    if ($c[self::FIELD_AT] < $time) {
                        $time = $c[self::FIELD_AT];
                        $seed = $s;
                    }
                }

                // remove the furthest access client
                unset($_client_list[$seed]);
            }
        }

        # print("#_update: ");
        # print_r($_sess_data);

        return $_sess_data;
    }

    /* flush the current session data to the driver */
    public function flush()
    {
        if ($this->_sess_uid == null 
            || $this->_need_flush == false) {
            return true;
        }

        $_sess_data = null;
        $this->reload();

        /* invoke the #_add to create an add atomic operation */
        if ($this->_row_exists == false) {
            for ($i = 0; $i < $this->_max_retries; ) {
                $_sess_data = $this->_getDataForFlush();
                if ($this->_add($this->_sess_uid, 
                    json_encode($_sess_data), $errno) == true) {
                    $this->_sess_data  = $_sess_data;
                    $this->_need_flush = false;
                    return true;
                }

                # reload the data for CAS failed
                # and continue the follow #_update operation.
                if ($errno == self::CAS_FAILED) {
                    $this->reload();
                    $i = -1;
                    break;
                } else {
                    $i++;
                }
            }

            # failed with retries
            if ($i != -1) {
                return false;
            }
        }

        // @Note: do the best to make sure the data _update succeed.
        // normal error will keep retry for #_max_retries times and for
        // CAS error it will keep trying util it succeed. */
        for ($i = 0; $i < $this->_max_retries; ) {
            $_sess_data = $this->_getDataForFlush();
            if ($this->_update($this->_sess_uid, 
                json_encode($_sess_data), $this->_cas_token, $errno) == true) {
                # reset the flush need mark to false
                # if the _update operation succeed
                $this->_sess_data  = $_sess_data;
                $this->_need_flush = false;
                return true;
            }

            # keep trying for cas failed
            # and limited retries for normal failed.
            if ($errno == self::CAS_FAILED) {
                $this->reload();
            } else {
                $i++;
            }
        }

        return false;
    }
    
    /* close the current session
     * this will force to flush the data by invoking the #_update(id, data).
     * And then clean up the session data */
    public function close()
    {
        $this->flush();
        $this->_sess_data = [];
    }

    /* destroy the current session. */
    public function destroy()
    {
        // delete the current client
        $this->delClient($this->_sess_seed);
        $this->close();

        // check and delete the session cookie
        if (isset($_COOKIE[$this->_sess_name])) {
            setcookie(
                $this->_sess_name, 
                '', 
                time() - 43200, 
                '/', 
                $this->_cookie_domain, 
                false, true
            );
        }
    }

    /* destroy all the register clients.
     * this function will make sure all clients removed.
     * this should be calling from the expert manager ONLY */
    public function destroyAll()
    {
        if ($this->_sess_uid == null) {
            return false;
        }

        $this->_row_exists = true;
        $this->_need_flush = true;
        $this->_create_new = false;

        do {
            # mark all the clients as removed
            $_client_list = &$this->_sess_data[self::FIELD_CLIENT];
            foreach ($_client_list as $k => $v) {
                $_client_list[$k][self::FIELD_ST] = self::STATUS_RM;
            }

            # flush the changes
            # and return false if operation failed.
            if ($this->flush() == false) {
                return false;
            }
        } while (count($this->_sess_data[self::FIELD_CLIENT]) > 0);

        // remove the row from the driver
        $this->_delete($this->_sess_uid);

        return true;
    }

    /**
     * The write callback is called when the session needs to be saved and closed
     * for the first time it created and there is NO row exists in the storage driver.
     * @Note: errno will set to CAS_FAILED if row exists.
     *
     * @see     #_update($uid, $val, $cas_token, &$errno=self::OK)
     * @param   $uid
     * @param   $val
     * @param   $errno
     * @return  bool
    */
    protected abstract function _add($uid, $val, &$errno=self::OK);

    /**
     * The read callback must always return a session encoded (serialized) string,
     *  or an empty string if there is no data to read.
     * This callback is called internally when the session starts.
     * @Note: the _read implementation should return the cas_token for CAS operation.
     *
     * @param   $uid
     * @param   $cas_token the cas token will return
     * @param   $exists
     * @return  string
    */
    protected abstract function _read($uid, &$cas_token, &$exists=true);

    /**
     * The write callback is called when the session needs to be saved and closed
     * for the row exists in the storage driver ONLY.

     * The serialized session data passed to this callback should be stored against
     * the passed session ID. When retrieving this data, the read callback must
     * return the exact value that was originally passed to the write callback.
     *
     * @Note: 
     * 1, the _update implementation should use the cas_token for compare and set.
     * 2, perform failed on exists add operation if the cas_token is null
     *
     * @param   $uid
     * @param   $val
     * @param   $cas_token
     * @param   $errno
     * @return  bool
    */
    protected abstract function _update($uid, $val, $cas_token, &$errno=self::OK);

    /**
     * This callback is executed when a session is detected to be destroyed.
     * Return value should be true for success, false for failure.
     *
     * @param   $uid
     * @return  bool
    */
    protected abstract function _delete($uid);


    /* get the current session name */
    public function getName()
    {
        return $this->_sess_name;
    }

    /* get the current session id
     * invoke it after the call of the start */
    public function getId()
    {
        return $this->_sess_id;
    }

    /* set the current session id
     * only a-z,0-9,A-Z is valid chars for the new session id
     * invoke it before the call of the start */
    public function setId($id)
    {
        $this->_sess_id = $id;
        return $this;
    }

    /* return the final UID passed in #start 
     * and used for data retrieving and stored */
    public function getUid()
    {
        return $this->_sess_uid;
    }

    /* set the final UID passed in #start 
     * and used for data retrieving and stored.
     * @Note this method should be invoke before the #start
     * And this usually use in an debug mode or for experts operations.
     * set the uid after #start may cause the invalid result from #_read.
    */
    public function setUid($uid)
    {
        $this->_sess_uid = $uid;
        return $this;
    }

    /* return the seed of the session */
    public function getSeed()
    {
        return $this->_sess_seed;
    }


    /* return the clients number with removed clients ignored 
     * count ONLY the status=OK clients if ok_only set to true.
     *
     * @param   $ok_only 
    */
    public function getClientSize($ok_only=false)
    {
        $size = 0;
        foreach ($this->_sess_data[self::FIELD_CLIENT] as $conf) {
            if ($ok_only == false) {
                $size++;
            } else if ($conf[self::FIELD_ST] == self::STATUS_OK) {
                $size++;
            }
        }
        return $size;
    }

    /* delete the specified client with its seed */
    protected function delClient($seed)
    {
        $this->_need_flush = true;
        $client = &$this->_sess_data[self::FIELD_CLIENT][$seed];
        $client[self::FIELD_ST] = self::STATUS_RM;
        return $client;
    }

    /* return the register address/at/counter item eg ... */
    public function getClientItem($field)
    {
        $clients = $this->_sess_data[self::FIELD_CLIENT];
        return $clients[$this->_sess_seed][$field] ?? null;
    }

    /* set the register address/at/counter item eg ... */
    protected function setClientItem($field, $val)
    {
        $clients = &$this->_sess_data[self::FIELD_CLIENT];
        if (isset($clients[$this->_sess_seed])) {
            $this->_need_flush = true;
            $clients[$this->_sess_seed][$field] = $val;
        }
    }

    /* increase the register address/at/counter item eg ... */
    protected function incClientItem($field, $offset=1)
    {
        $clients = &$this->_sess_data[self::FIELD_CLIENT];
        if (isset($clients[$this->_sess_seed])) {
            $this->_need_flush = true;
            $clients[$this->_sess_seed][$field] += $offset;
        }
    }


    /* check if the specifed key is exists in the session data */
    public function has($key)
    {
        return isset($this->_sess_data[$key]);
    }

    /* return the value of the specified key or null for no mapping */
    public function get($key, $default=null)
    {
        return isset($this->_sess_data[$key])
            ? $this->_sess_data[$key] : $default;
    }

    public function set($key, $val)
    {
        $this->_need_flush = true;
        $this->_sess_data[$key] = $val;
        $this->_track($key, self::LOG_SET, $val);
        return $this;
    }

    public function del($key)
    {
        if (isset($this->_sess_data[$key])) {
            $this->_need_flush = true;
            unset($this->_sess_data[$key]);
            $this->_track($key, self::LOG_DEL, true);
        }
        return $this;
    }


    /* session data operation log.
     * a way to ensure the success of the data updates
     * during a CAS operation env. 
     * log item:
     * ['#field' => [
     *  'S' => 'val',
     *  'I' => '+offset',
     *  'D' => true | false,
     * ]]
     *
     * @Note: increase is an operation that cannot be
     * precisely controlled, so deprecated it.
     * do not rely on the data managed by the inc operation.
     */
    const LOG_INC = 'I';    # increase operation
    const LOG_SET = 'S';    # set operation
    const LOG_DEL = 'D';    # delete operation
    protected $_sess_log  = [];
    private function _track($f, $opt, $val)
    {
        if (!isset($this->_sess_log[$f])) {
            $this->_sess_log[$f] = array();
        }

        if ($opt == self::LOG_INC) {
            // do nothing right now
        } else {
            $this->_sess_log[$f][$opt] = $val;
        }
    }


    public function __destruct()
    {
        $this->close();
    }

}

 //----------------------------------------------------

/**
 * Session factory - quick way to lanch all kinds of session implements with just a key
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-------------------------------------------------------

class SessionFactory
{

    private static $_classes = NULL;
    
    /**
     * Load and create the instance of a specifield session class
     *      with a specifield key, then return the instance
     *  And it will make sure the same class will only load once
     *
     * @param   $_class class key
     * @param   $_args  arguments to initialize the instance
    */
    public static function create($_class, $_conf=NULL)
    {
        if ( self::$_classes == NULL ) self::$_classes = array();
        
        //Fetch the class
        $_class = ucfirst( $_class ) . 'Session';
        if ( ! isset( self::$_classes[$_class] ) ) {
            require  dirname(__FILE__) ."/{$_class}.class.php";
            self::$_classes[$_class] = true;
        }
        
        //return the newly created instance
        return new $_class($_conf);
    }

}
