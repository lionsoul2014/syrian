<?php
/**
 * dynamic content redis NoSQL cache class.
 * base on the php extension of phpredis(https://github.com/phpredis/phpredis)
 *
 * @author chenxin<chenxin619315@gmail.com>
 * @author koma <komazhang@foxmail.com>
*/
class RedisCache implements ICache
{
    private $_ttl        = 0;
    private $_errno      = 0; // 0-success, 1-connect error,
    private $_redis      = null;
    private $_prefix     = '';
    private $_key        = '';
    private $_baseKey    = '';
    private $_fname      = '';
    private $_serverList = array();

    public function __construct( $conf = NULL )
    {
        if ( !isset($conf['servers']) || empty($conf['servers']) ){
           throw new Exception('Redis server should not be empty'); 
        }

        if ( isset($conf['ttl']) )    $this->_ttl    = intval($conf['ttl']);
        if ( isset($conf['prefix']) ) $this->_prefix = $conf['prefix'];
        
        $this->_serverList = $conf['servers'];
    }

    //----------------- string functoins --------------

    public function get($time=NULL, $callback=null)
    {
        return $this->getByKey($this->_key, $callback);
    }

    /**
     * store data into redis server
     * because we use the REDIS::OPT_SERIALIZER
     * so we can store every type of values
     * 
     * @param mixed $data 
     * @param intger $ttl
     * @param $mode not used
     *
     * @return boolean
    **/
    public function set( $data, $ttl = NULL, $mode = NULL)
    {
        return $this->setByKey($this->_key, $data, $ttl);
    }

    public function getByKey($key, $callback=null)
    {
        if ( $key == '' )          return false;
        if ( !$this->_conn($key) ) return false;

        $ret = $this->_redis->get($this->_key);
        if ( $ret != false && $callback != null ) {
            return $callback($ret);
        }

        return $ret;
    }

    public function setByKey($key, $data, $ttl)
    {
        if ( $key == '' )          return false;
        if ( !$this->_conn($key) ) return false;

        $_ttl = intval($ttl) > 0 ? intval($ttl) : $this->_ttl;
        if ( $_ttl > 0 ) {
            $ret = $this->_redis->set($key, $data, $_ttl);
        } else {
            $ret = $this->_redis->set($key, $data);
        }

        return $ret;
    }

    //----------------- base functoins --------------

    public function baseKey( $baseKey )
    {
        if ( $baseKey != NULL ) $this->_baseKey = $baseKey;
        $this->_key = $this->_prefix . $this->_baseKey . $this->_fname;

        return $this;
    }

    public function factor( $factor )
    {
        return $this;
    }

    public function fname( $fname )
    {
        if ( $fname != NULL ) $this->_fname = $fname;
        $this->_key = $this->_prefix . $this->_baseKey . $this->_fname;
        
        return $this;
    }

    public function setTtl ( $ttl ) 
    {
        if( ($_ttl = intval($_ttl)) < 0 ) {
            $_ttl = 0;
        }
        $this->_ttl = $_ttl;
        
        return $this;
    }

    public function exists()
    {
        if ( !$this->_conn() ) return false;

        return $this->_redis->exists($this->_key);
    }

    public function remove()
    {
        return $this->removeByKey($this->_key);
    }

    public function removeByKey($key = null)
    {
        if ( $key == '' )          return false;
        if ( !$this->_conn($key) ) return false;

        return ($this->_redis->delete($key)) > 0;
    }

    /**
     * remove all the keys in $keys array
     *
     * @param array $keys 
     * 
     * @return boolean
    **/
    public function mRemove( $keys )
    {
        if ( empty($keys) ) return false;

        $_sn = 0;
        foreach ( $keys as $key ) {
            $this->removeByKey($key) && $_sn++;
        }
        
        return ($_sn > 0);
    }

    public function errno()
    {
        /**
         * get the last command execute errno
         * just like the unix errno
         *
        **/
        return $this->_errno;
    }

    private function _conn($key = null)
    {
        static $_connServer = array();

        $_key = $key == null ? $this->_key : $key;
        $server = $this->_serverList[self::_hash($_key) % count($this->_serverList)];

        $_k = sha1("{$server[0]}@{$server[1]}");
        if ( isset($_connServer[$_k]) ) {
            $this->_redis = $_connServer[$_k];

            if ( $this->_redis->isConnected() ) {
                return true;
            }
        }

        $_redis = new Redis();
        if ( !$_redis->connect($server[0], $server[1], $server[2], null, $server[3])
            || !$_redis->isConnected() ) {
            $this->_errno = 1;
            return false;
        }

        //use php built-in serialize/unserialize as the serializer
        $_redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->_redis = $_connServer[$_k] = $_redis;
        unset($_redis);

        return true;
    }

    /**
     * bkdr hash algorithm
     *
     * @param   $str
     * @return  Integer hash value
    */
    private static function _hash($str)
    {
        $hval = 0;
        $len  = strlen($str);
    
        /*
         * 4-bytes integer we will directly take
         * its int value as the final hash value.
        */
        if ( $len <= 11 && is_numeric($str) ) {
            $hval = intval($str);
        } else {
            for ( $i = 0; $i < $len; $i++ ) {
                $hval = (int) ($hval * 1331 + (ord($str[$i]) % 127));
            }
        }
        
        return ($hval & 0x7FFFFFFF);
    }
}