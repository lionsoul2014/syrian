<?php
/**
 * totally rewrite session handler class and base on memcached
 * and this one is not base on the php internal session
 *    
 * @author  chenxin<chenxin619315@gmail.com>
*/

class MempureSession implements ISession
{
    private static $_hash_opts = array(
        'default'   => Memcached::HASH_DEFAULT,
        'md5'       => Memcached::HASH_MD5,
        'crc'       => Memcached::HASH_CRC,
        'fnv1_64'   => Memcached::HASH_FNV1_64,
        'fnv1a_64'  => Memcached::HASH_FNV1A_64,
        'fnv1_32'   => Memcached::HASH_FNV1_32,
        'fnv1a_32'  => Memcached::HASH_FNV1A_32,
        'hsieh'     => Memcached::HASH_HSIEH,
        'murmur'    => Memcached::HASH_MURMUR
    );

    private $_ttl           = 0;
    private $_session_name  = 'PHPSESSID';

    private $_sessid    = null;
    private $_R8C       = null;
    private $_session   = null;
    private $_override  = false;
    private $_mem       = null;

    //@see ./FileSession
    private $_expire_strategy = 'request';
    private $_cookie_extra    = 0;
    private $_cookie_domain   = null;

    /**
     * reuse strategy
    */
    private $_reuse_strategy = null;

    /**
     * construct method to initialize the class
     * 
     * demo config data:
     *  $_conf = array(
     *       'servers'       => array(
     *           array('localhost', 11211, 60), // host, port, weight
     *           array('localhost', 11212, 40),
     *       ),
     *       'ttl'           => 60, // time to live
     *       // default: standard,  consistent was recommended,
     *       // for more infomation,  search 'consistent hash'
     *       'hash_strategy' => 'consistent',
     *       'hash'          => 'default', // hash function,  empty for default
     *       'prefix'        => 'ses_'
     *   );
     *  
     * @param   $conf
    */
    public function __construct( $conf )
    {
        if ( ! isset($conf['servers']) || empty($conf['servers']) ) {
           throw new Exception('Empty servers'); 
        }

        if ( isset($conf['ttl']) ) $this->_ttl = $conf['ttl'];
        if ( isset($conf['reuse_strategy']) )
            $this->_reuse_strategy  = $conf['reuse_strategy'];
        if ( isset($conf['expire_strategy']) )
            $this->_expire_strategy = $conf['expire_strategy'];
        if ( isset($conf['session_name']) )
            $this->_session_name = $conf['session_name'];
        if ( isset($conf['cookie_extra']) ) {
            $this->_cookie_extra = $conf['cookie_extra'];
        }
            
        $this->_mem = new Memcached();

        // hash distribute strategy, 
        // default: Memcached::DISTRIBUTION_MODULA
        if ( isset($conf['hash_strategy']) ) {
            switch ( $conf['hash_strategy'] ) {
            case 'consistent':
                $this->_mem->setOption(Memcached::OPT_DISTRIBUTION, 
                    Memcached::DISTRIBUTION_CONSISTENT); 
                $this->_mem->setOPtion(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                break;
            }
        }

        if ( isset($conf['hash']) ) {
            $hash = self::$_hash_opts[$conf['hash']];
            $this->_mem->setOption(Memcached::OPT_HASH, $hash); 
        }

        if ( isset($conf['prefix']) ) {
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $conf['prefix']);
        }

        $servers = $this->_mem->getServerList();
        if ( empty($servers) ) {
            $this->_mem->addServers($conf['servers']);
        }

        if ( isset($conf['cookie_domain']) ) {
            $this->_cookie_domain = $conf['cookie_domain'];
        } else if ( isset($conf['domain_strategy']) ) {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            switch ( $conf['domain_strategy'] ) {
            case 'cur_host': $this->_cookie_domain = $host; break;
            case 'all_sub_host':
                $pnum = 0;
                $hostLen = min(strlen($host), 255);
                for ( $i = 0; $i < $hostLen; $i++ ) {
                    if ( $host[$i] == '.' ) $pnum++;
                }
                
                //define the sub host ($pnum could be 0 like localhost)
                if ( $pnum == 0 ) $this->_cookie_domain = $host;
                else if ( $pnum == 1 ) $this->_cookie_domain = ".{$host}";
                else $this->_cookie_domain = substr($host, strpos($host, '.'));
                break;
            }
        }
    }

    public function start()
    {
        if ( $this->_sessid != null ) {}

        /*
         * try to fetch the session id from the GP data
        */
        else if ( isset($_REQUEST[$this->_session_name]) ) {
            $this->_sessid = $_REQUEST[$this->_session_name];
        }

        /**
         * try to fetch the session id from the cookie data
        */
        else if ( isset($_COOKIE[$this->_session_name]) ) {
            $this->_sessid = $_COOKIE[$this->_session_name];
        }

        /*
         * session id is not define or the client has not bring
         * the sended session id back here
         * we will generate one
        */
        else {
            import('Util');
            import('StringUtil');
            $ipaddr = Util::getIpAddress(true);

            if ( $this->_reuse_strategy == null ) {
                $this->_sessid = StringUtil::genGlobalUid($ipaddr);
            } else {
                $cache = helper($this->_reuse_strategy['helper'], $ipaddr);
                $jsArr = $cache->get(-1, 'json_decode_array');
                if ( $jsArr == false ) $jsArr = array();
                if ( count($jsArr) < $this->_reuse_strategy['max_num'] ) {
                    $this->_sessid = StringUtil::genGlobalUid($ipaddr);
                    $jsArr[$this->_sessid] = 1;
                } else {
                    $this->_sessid = key($jsArr);
                    unset($jsArr[$this->_sessid]);
                    $jsArr[$this->_sessid] = 1;
                }

                $cache->set(json_encode($jsArr));
            }

            $this->_expire_strategy = 'request';
            if ( $this->_R8C != null ) {
                $this->_sessid = "{$this->_sessid}---{$this->_R8C}";
            }
        }

        $this->_read();

        if ( $this->_expire_strategy == 'request' ) {
            $r8cVal = $this->_R8C;
            setcookie(
                $this->_session_name, 
                $r8cVal == null ? $this->_sessid : "{$this->_sessid}---{$r8cVal}", 
                time() + $this->_ttl + $this->_cookie_extra,
                '/',
                $this->_cookie_domain,
                false,
                true
            );
        }
    }

    //destroy the currrent session
    public function destroy()
    {
        //1. clear the session data
        unset($this->_session);
        $this->_session = array();

        //2. destroy the session file or stored data
        $this->_destroy();
    }

    public function flush()
    {
        return $this->_write();
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
    public function setSessionId( $_sessid )
    {
        //set the session id
        $this->_sessid = $_sessid;
        return $this;
    }
    
    /**
     * The read callback must always return a session encoded (serialized) string,
     *  or an empty string if there is no data to read.
    */
    private function _read()
    {
        if ( ($pos = strpos($this->_sessid, '---')) !== false ) {
            $this->_R8C = substr($this->_sessid, $pos + 3);
            $this->_sessid = substr($this->_sessid, 0, $pos);
        }

        if ( ($cc = $this->_mem->get($this->_sessid)) != false ) {
            $this->_session = json_decode($cc, true);
        } else {
            $this->_session = array();
        }
    }
    
    /**
     * The write callback is called when the session needs to be saved and closed.
     * The serialized session data passed to this callback should be stored against
     * the passed session ID. When retrieving this data, the read callback must
     * return the exact value that was originally passed to the write callback.
    */
    private function _write()
    {
        if ( $this->_override ) {
            $this->_override = false;
            $this->_mem->set(
                $this->_sessid, json_encode($this->_session), $this->_ttl
            );
        }

        return true;
    }
    
    /**
     * This callback is executed when a session is destroyed
    */
    private function _destroy()
    {
        $this->_mem->delete($this->_sessid);
        if ( isset($_COOKIE[$this->_session_name]) ) {
            setcookie($this->_session_name, '', time() - 86400, '/');
        }
    }
    
    //check the specifield mapping is exists or not
    public function has($key)
    {
        return isset($this->_session[$key]);
    }

    //get the value mapping with the specifield key
    public function get($key)
    {
        if ( ! isset($this->_session[$key]) ) return null;
        return $this->_session[$key];
    }

    //set the value mapping with the specifield key
    public function set($key, $val)
    {
        $this->_session[$key] = $val;
        $this->_override = true;
        return $this;
    }

    //get the R8C
    public function getR8C()
    {
        return $this->_R8C;
    }

    //set the current R8C invoke it before invoke start
    public function setR8C($r8c)
    {
        $this->_R8C = $r8c;
        return $this;
    }

    public function __destruct()
    {
        $this->_write();
    }

}
