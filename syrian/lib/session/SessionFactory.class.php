<?php
/**
 * session 2.0 base abstract implementation
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
abstract class SessionBase
{
    /* error constants for #start */
    const OK = 0x00;
    const EMPTY_SESS_ID = 0x01;
    const DECODE_FAILED = 0x02;
    const FIELD_MISSING = 0x03;
    const INVALID_SIGN  = 0x04;
    const INVALID_SEED  = 0x05;
    const INVALID_ADDR  = 0x06;

    /* session global vars */
    protected $_sess_data = [];
    protected $_sess_name = null;
    protected $_sess_id   = null;
    protected $_sess_uid  = null;

    /* common config item */
    protected $_ttl = 1800;    // default expire time to 30 mins
    protected $_cookie_domain = '';
    protected $_need_flush = true;

    /* the request that update the seed in the session data 
     * consider to be the primary one */
    protected $_is_primary = true;

    public function __construct($conf)
    {
        if (!isset($conf['session_name'])) {
            throw new Exception("Missing session_name config item");
        }

        $this->_sess_name = $conf['session_name'];
        if (isset($conf['ttl'])) {
            $this->_ttl = $conf['ttl'];
        }

        # check and get the cookie domain name
        if (isset($conf['cookie_domain'])) {
            $this->_cookie_domain = $conf['cookie_domain'];
        } else {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            switch ($conf['domain_strategy'] ?? 'cur_host') {
            case 'cur_host': $this->_cookie_domain = $host; break;
            case 'all_sub_host':
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
                break;
            }
        }
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
     * @return  bool
    */
    public function start($create_new=false, $uid=null, &$errno=self::OK)
    {
        if ($create_new == true) {
            import('Util');
            $seed = microtime(true);
            $addr = Util::getIpAddress(true);
            # check and generate the uid
            if ($uid == null) {
                $uid = build_signature(array(
                    '_sess_uid', $seed, $addr, mt_rand(0,13333), mt_rand(0, 13331)
                ));
            }

            $this->_sess_uid = $uid;
            $this->_sess_id  = base64_encode(json_encode(array(
                'uid'  => $this->_sess_uid,
                'seed' => $seed,
                'addr' => $addr,
                'sign' => build_signature(array('_sess_id', $this->_sess_uid, $seed, $addr))
            )));

            # track the seed for the newly created session pack
            $this->_sess_data['__sd'] = $seed;
            $this->_sess_data['__ar'] = $addr;
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
            $this->_sess_uid = $obj->uid;
            $this->reload(false);

            # 5, random seed checking, basicly the login timestamp
            # @Note: we keep things going if the seed is not match.
            # so the parent invoker could do whatever it want
            #   according to the errno.
            if (!isset($this->_sess_data['__sd']) 
                || $this->_sess_data['__sd'] != $obj->seed) {
                $errno = self::INVALID_SEED;
                $this->_is_primary = false;
                # return false;
                # @Note: keep things going
            }

            // extra fields to update
            if (!empty($this->_sess_data)) {
                $this->set('__at', microtime(true));
                $this->inc('__ct', 1);
            }
        }

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
    public function reload($override=false)
    {
        if ($this->_sess_uid != null) {
            $_data_str = $this->_read($this->_sess_uid);
            if (strlen($_data_str) > 2 
                && ($arr = json_decode($_data_str, true)) != null) {
                $this->_sess_data = $override 
                    ? $arr : array_merge($this->_sess_data, $arr);
            }
        }
        return $this;
    }

    /* flush the current session
     * this will force to invoking the #_write(id, data) */
    public function flush()
    {
        if ($this->_sess_uid != null && $this->_need_flush) {
            if ($this->_write($this->_sess_uid, 
                json_encode($this->_sess_data)) == true) {
                $this->_need_flush = false;
            }
        }
        return $this;
    }
    
    /* close the current session
     * this will force to flush the data by invoking the #_write(id, data).
     * And then clean up the session data */
    public function close()
    {
        $this->flush();
        $this->_sess_data = [];
        return $this;
    }

    /* destroy the current session.
     * @Note: 
     * ONLY the primary request could invoke the internal #_destroy */
    public function destroy()
    {
        if ($this->_sess_uid != null && $this->isPrimary()) {
            if ($this->_destroy($this->_sess_uid) == true) {
                $this->_sess_data = [];
            }
        }

        // delete the cookies
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

        // basic clean up and force not to flush the data
        // in case the data were write to the driver again
        // by calling flush/close or destruct the instance
        $this->_sess_data = [];
        $this->_need_flush = false;
        return $this;
    }

    /**
     * The read callback must always return a session encoded (serialized) string,
     *  or an empty string if there is no data to read.
     * This callback is called internally when the session starts.
     *
     * @return string
    */
    protected abstract function _read($uid);

    /**
     * The write callback is called when the session needs to be saved and closed.
     * The serialized session data passed to this callback should be stored against
     * the passed session ID. When retrieving this data, the read callback must
     * return the exact value that was originally passed to the write callback.
     *
     * @return bool
    */
    protected abstract function _write($uid, $str);

    /**
     * This callback is executed when a session is destroyed.
     * Return value should be true for success, false for failure.
     *
     * @return bool
    */
    protected abstract function _destroy($uid);


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

    /* get the size of the session data */
    public function size()
    {
        return count($this->_sess_data);
    }

    /* @see #_is_primary */
    public function isPrimary()
    {
        return $this->_is_primary;
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

    /* return the seed stored in the session data */
    public function getSeed()
    {
        return $this->get('__sd');
    }

    /* return the IP stored in the session data */
    public function getAddr()
    {
        return $this->get('__ar');
    }

    /* increase a numeric value by a specified offset */
    public function inc($key, $offset=1)
    {
        if (isset($this->_sess_data[$key])) {
            $this->_sess_data[$key] += $offset;
        } else {
            $this->_sess_data[$key]  = $offset;
        }
        return $this;
    }

    public function set($key, $val)
    {
        $this->_sess_data[$key] = $val;
        $this->_need_flush = true;
        return $this;
    }

    public function del($key)
    {
        if (isset($this->_sess_data[$key])) {
            $this->_sess_data[$key] = null;
            unset($this->_sess_data[$key]);
            $this->_need_flush = true;
        }
        return $this;
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
