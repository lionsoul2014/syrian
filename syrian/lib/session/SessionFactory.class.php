<?php
/**
 * session 2.0 base abstract implementation
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
abstract class SessionBase
{

    /* session global vars */
    protected $_sess_data = [];
    protected $_sess_name = null;
    protected $_sess_id   = null;
    protected $_sess_uid  = null;

    /* common config item */
    protected $_ttl = 1800;    // default expire time to 30 mins
    protected $_cookie_domain = '';
    protected $_need_flush = true;

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
     * uid: uid should be use to for final data access not the json pack.
    */
    public function start($uid=null)
    {
        $sign_check = true;
        if ($this->_sess_id != null) {
            # initialize by #setId
        } else if (isset($_COOKIE[$this->_sess_name])) {
            # check the cookie first
            $this->_sess_id = $_COOKIE[$this->_sess_name];
        } else if (isset($_REQUEST[$this->_sess_name])) {
            # check session id from GET/POST
            $this->_sess_id = urldecode($_REQUEST[$this->_sess_name]);
        } else {
            import('Util');
            $seed = microtime(true);
            $addr = Util::getIpAddress(true);
            # check and generate the uid
            if ($uid == null) {
                $uid = build_signature(array(
                    '_sess_uid', $seed, $addr, mt_rand(0,13333), mt_rand(0, 13331)
                ));
            }

            $sign_check = false;
            $this->_sess_uid = $uid;
            $this->_sess_id  = base64_encode(json_encode(array(
                'uid'  => $this->_sess_uid,
                'seed' => $seed,
                'addr' => $addr,
                'sign' => build_signature(array('_sess_id', $this->_sess_uid, $seed, $addr))
            )));
        }

        // check and do session id signature checking
        if ($sign_check == true) {
            # 1, data decode
            if (($idval = base64_decode($this->_sess_id)) === false 
                || ($obj = json_decode($idval, false)) == null) {
                $this->destroy();   # destroy the error session
                return false;
            }

            # 2, fields structure checking
            unset($idval);
            foreach (array('uid', 'seed', 'addr', 'sign') as $f) {
                if (isset($obj->{$f}) == false) {
                    $this->destroy();   # destroy the error session
                    return false;
                }
            }

            # 3, sign checking
            if (strcmp($obj->sign, build_signature(array(
                '_sess_id', $obj->uid, $obj->seed, $obj->addr))) != 0) {
                $this->destroy();   # destroy the error session
                return false;
            }

            # 4, basic initialize and load the data from the driver
            $this->_sess_uid = $obj->uid;
            $_data_str = $this->_read($obj->uid);
            if (strlen($_data_str) > 2 
                && ($arr = json_decode($_data_str, true)) != null) {
                $this->_sess_data = array_merge($this->_sess_data, $arr);
            }
        }

        // check and update the session cookie
        // if the session id were passed through the HTTP cookie
        header("HTTP-SESSION-NAME: {$this->_sess_name}");
        header("HTTP-SESSION-ID: {$this->_sess_id}");
        setcookie(
            $this->_sess_name, 
            $this->_sess_id, 
            time() + $this->_ttl, 
            '/', 
            $this->_cookie_domain, 
            false, true
        );

        // extra fields to update
        $this->set('__at', microtime(true));
        $this->set('__ct', $this->get('__ct', 1) + 1);

        return true;
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

        return true;
    }
    
    /* close the current session
     * this will force to flush the data by invoking the #_write(id, data).
     * And then clean up the session data */
    public function close()
    {
        $this->flush();
        $this->_sess_data = [];
        return true;
    }

    /* destroy the current session */
    public function destroy()
    {
        if ($this->_sess_uid != null) {
            if ($this->_destroy($this->_sess_uid) == true) {
                $this->_sess_data = [];
            }
        }

        // delete the cookies
        if (isset($_COOKIE[$this->_sess_name])) {
            setcookie($this->_sess_name, '', time() - 43200, '/');
        }

        return true;
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

    /* return the final UID passed in #start and used for data retrieving and stored */
    public function getUid()
    {
        return $this->_sess_uid;
    }

    /* check if the specifed key is exists in the session data */
    public function has($key)
    {
        return isset($this->_sess_data[$key]);
    }

    /* return the value of the specified key or null for no mapping */
    public function get($key, $default=null)
    {
        return isset($this->_sess_data[$key]) ? $this->_sess_data[$key] : $default;
    }

    /* increase a numeric value by a specified offset */
    public function inc($key, $offset=1)
    {
        if (isset($this->_sess_data[$key])) {
            $this->_sess_data[$key] += $offset;
        } else {
            $this->_sess_data[$key]  = $offset;
        }
    }

    public function set($key, $val)
    {
        $this->_sess_data[$key] = $val;
        $this->_need_flush = true;
    }

    public function del($key)
    {
        if (isset($this->_sess_data[$key])) {
            $this->_sess_data[$key] = null;
            unset($this->_sess_data[$key]);
            $this->_need_flush = true;
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
