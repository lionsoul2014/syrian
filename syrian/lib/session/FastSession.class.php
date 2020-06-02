<?php
/**
 * user level session class and base on the client INPUT ONLY
 * which is much faster than the implements based on ANY storage media.
 * all the data in the this session is public so it is not good choice
 * for private data situation.
 *    
 * @author chenxin<chenxin619315@gmail.com>
*/

class FastSession implements ISession
{

    /* default the session life time to 10mins */
    private $_ttl       = 600;
    private $_sess_name = 'sy_sess_d';
    private $_sess_id   = null;
    private $_sess_pack = null;;
    private $_sess_data = null;

    // @see ./FileSession
    private $_expire_strategy = 'request';
    private $_cookie_domain   = '';

    /**
     * construct method to initialize the class
     * 
     * demo config data:
     *  $_conf = array(
     *       'ttl'           => 86400,  // time to live
     *       'session_name'  => 'sy_sess_d',
     *       // domain strategy cur_host | all_sub_host
             'domain_strategy' => 'all_sub_host'
     *   );
     *  
     * @param   $conf
     */
    public function __construct($conf)
    {
        if (isset($conf['ttl'])) {
            $this->_ttl = $conf['ttl'];
        }

        if (isset($conf['cookie_domain'])) {
            $this->_cookie_domain = $conf['cookie_domain'];
        } else if (isset($conf['domain_strategy'])) {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            switch ($conf['domain_strategy']) {
            case 'cur_host': $this->_cookie_domain = $host; break;
            case 'all_sub_host':
                $pnum = 0;
                $hostLen = min(strlen($host), 255);
                for ( $i = 0; $i < $hostLen; $i++ ) {
                    if ( $host[$i] == '.' ) $pnum++;
                }
                
                //define the sub host ($pnum could be 0 like localhost)
                if ($pnum == 0) $this->_cookie_domain = $host;
                else if ($pnum == 1) $this->_cookie_domain = ".{$host}";
                else $this->_cookie_domain = substr($host, strpos($host, '.'));
                break;
            }
        }

        if ($this->_expire_strategy == 'global') {
            session_set_cookie_params($this->_ttl, '/', $this->_cookie_domain);
        }
    }

    /* start the session */
    public function start()
    {
        if ( $this->_sess_id != null ) {
        }
        /* try to fetch the session id from the GP data */
        else if ( isset($_REQUEST[$this->_session_name]) ) {
            $this->_sess_id = $_REQUEST[$this->_session_name];
        }
        /* try to fetch the session id from the cookie data */
        else if ( isset($_COOKIE[$this->_session_name]) ) {
            $this->_sess_id = $_COOKIE[$this->_session_name];
        }
        /*
         * session id is not define or the client has not bring
         * the sended session id back here we will generate one
        */
        else {
            $this->_sess_pack = self::_build_sess_pack(null);
        }

        /* check and decode the session data */
        if ($this->_sess_pack == null) {
            $this->_sess_pack = $this->_read();
        }
    }

    /* destroy the currrent session */
    public function destroy()
    {
        // 1. clear the session data
        unset($this->_session);
        $this->_session = array();

        // 2. destroy the session file or stored data
        $this->_destroy();
    }

    // get the current session id
    // invoke it after the invoke the start method
    public function getSessionId()
    {
        return $this->_sess_id;
    }

    // set the current session id
    // only a-z,0-9,A-Z is valid chars for the new session id
    // invoke it before the invoke of method start
    public function setSessionId($_sess_id)
    {
        $this->_sess_id = $_sess_id;
        return $this;
    }
    
    /**
     * The read callback must always return a session encoded (serialized) string,
     *  or an empty string if there is no data to read.
    */
    function _read()
    {
        if ( $this->_sess_id == null 
            || ($data = base64_decode($this->_sess_id)) == false 
                || ($json = json_decode($data, true)) == null) {
            return array();
        }
        
        foreach (array('id', 'dt', 'sg') as $k) {
            if (isset($json[$k]) == false) {
                return array();
            }
        }

        /* check the signature */
        if (valid_signature(array($json['id'], 
            json_encode($json['dt'])), $sg, $this->ttl) == false) {
            return array();
        }

        /* reload the data to the global session */
        $this->_sess_data = $json['dt'];
        $this->_sess_pack = array(
            'id' => $json['id'],
            'dt' => $json['dt'],
            'sg' => $json['sg']
        );

        unset($data, $json);
        return $this->_sess_pack;
    }

    /**
     * The write callback is called when the session needs to be saved and closed.
     * The serialized session data passed to this callback should be stored against
     * the passed session ID. When retrieving this data, the read callback must
     * return the exact value that was originally passed to the write callback.
    */
    private function _write()
    {
        if ($this->_expire_strategy == 'request') {
            setcookie(
                $this->_session_name, 
                $this->_sess_id,
                time() + $this->_ttl,
                '/',
                $this->_cookie_domain,
                false,
                true
            );
        }
    }
    
    /* build a session package with the specifield data */
    private function _build_sess_pack($data)
    {
        import('Util');
        import('StringUtil');

        $id = StringUtil::genGlobalUid(Util::getIpAddress(true));
        $dt = $data == null ? array() : $data;
        $sg = build_signature(array($id, json_encode($dt)), $this->ttl);

        return array(
            'id' => $id,
            'dt' => $dt,
            'sg' => $sg
        );
    }

    /**
     * This callback is executed when a session is destroyed with session_destroy().
     * Return value should be true for success, false for failure.
    */
    function _destroy($_sess_id)
    {
        if ( isset($_COOKIE[$this->_session_name]) ) {
            setcookie($this->_session_name, '', time() - 86400, '/');
        }

        return true;
    }
    
    // check the specifield mapping is exists or not
    public function has($key)
    {
        return isset($this->_sess_data[$key]);
    }

    // get the value mapping with the specifield key
    public function get($key)
    {
        return isset($this->_sess_data[$key]) ? $this->_sess_data[$key] : null;
    }

    // set the value mapping with the specifield key
    public function set($key, $val)
    {
        $this->_sess_data[$key] = &$val;
        return $this;
    }

    public function getR8C() {}
    public function setR8C($r8c) {}

    public function __destruct()
    {
        $this->_write();
    }

}
