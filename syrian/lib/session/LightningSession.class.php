<?php
/**
 * user level session handler class and base on the client cookies ONLY
 * which is much faster than the implements based on ANY storage media.
 *    
 * @author chenxin<chenxin619315@gmail.com>
*/

class LightningSession implements ISession
{
    private static $_hash_opts = array(
        'md5'       => true,
        'sha1'      => true,
        'sha256'    => true,
        'sha512'    => true
    );

    private $_ttl     = 0;
    private $_sessid  = null;

    // @see ./FileSession
    private $_expire_strategy = 'request';
    private $_cookie_domain   = '';


    /**
     * construct method to initialize the class
     * 
     * demo config data:
     *  $_conf = array(
     *       'ttl'           => 86400,  // time to live
     *       'sign_strategy' => 'sha1',
             'session_name'  => 'SR_SESSID',
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

        // hash distribute strategy, 
        // default: Memcached::DISTRIBUTION_MODULA
        if ( isset($conf['sign_strategy']) ) {
            switch ($conf['sign_strategy']) {
            case 'md5':
                break;
            case 'sha1':
                break;
            case 'sha256':
                break;
            case 'sha512':
                break;
            }
        }

        // set use user level session
        session_module_name('user');
        session_set_save_handler(
            array($this, '_open'),
            array($this, '_close'),
            array($this, '_read'),
            array($this, '_write'),
            array($this, '_destroy'),
            array($this, '_gc')
        );

        if (isset($conf['session_name'])) {
            session_name($conf['session_name']);
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
        // set the session id and start the session
        session_id($this->_sessid);
        session_start();

        /* check the expire_strategy and extend the cookies life time as needed */
        if ($this->_expire_strategy == 'request') {
            setcookie(
                session_name(),
                '{id:xxxx}',
                time() + $this->_ttl,
                '/',
                $this->_cookie_domain,
                false,
                true
            );
        }
    }

    // destroy the current session
    public function destroy()
    {
        // 1. clear the session data
        session_unset();    

        // 2. destroy the session file or stored data
        if ($this->_sessid != null) {
            $this->_destroy($this->_sessid);
        }
        
        // 3. destroy the session
        session_destroy();
    }

    //get the current session id
    //invoke it after the invoke the start method
    public function getSessionId()
    {
        return $this->_sessid;
    }

    //set the current session id
    //only a-z,0-9,A-Z is valid chars for the new session id
    //invoke it before the invoke of method start
    public function setSessionId($_sessid)
    {
        //set the session id
        $this->_sessid = $_sessid;
        return $this;
    }
    
    /**
     * It is the first callback function executed when the session
     *  is started automatically or manually with session_start().
     * Return value is true for success, false for failure.
     */
    function _open($_save_path, $_sessname)
    {
        return true;
    }

    /**
     * It is also invoked when session_write_close() is called.
     * Return value should be true for success, false for failure.
    */
    function _close()
    {
        return true;
    }
    
    /**
     * The read callback must always return a session encoded (serialized) string,
     *  or an empty string if there is no data to read.
     * This callback is called internally by PHP when the session starts or when session_start()
     *  is called. Before this callback is invoked PHP will invoke the open callback.
    */
    function _read($_sessid)
    {
        return '';
    }
    
    /**
     * The write callback is called when the session needs to be saved and closed.
     * The serialized session data passed to this callback should be stored against
     * the passed session ID. When retrieving this data, the read callback must
     * return the exact value that was originally passed to the write callback.
    */
    function _write($_sessid, $_data)
    {
        return true;
    }
    
    /**
     * This callback is executed when a session is destroyed with session_destroy().
     * Return value should be true for success, false for failure.
    */
    function _destroy($_sessid)
    {
        // delete the PHP session cookies
        $sessname = session_name();
        if ( isset($_COOKIE[$sessname]) ) {
            setcookie($sessname, '', time() - 42000, '/');
        }

        return true;
    }
    
    /**
     * The garbage collector callback is invoked internally by PHP periodically
     *  in order to purge old session data.
     * The frequency is controlled by session.gc_probability and session.gc_divisor. 
    */
    function _gc($_lifetime)
    {
        return true;
    }

    //check the specifield mapping is exists or not
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    // get the value mapping with the specifield key
    public function get($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    // set the value mapping with the specifield key
    public function set($key, $val)
    {
        $_SESSION[$key] = &$val;
        return $this;
    }

}
