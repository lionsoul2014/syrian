<?php
/**
 * list implementation base on Redis
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
class RedisList implements IList
{

    /**
     * the auto insert prefix
    */
    private $prefix = null;
    
    /**
     * the key for the current list
    */
    private $key = null;

    /**
     * the TTL for the current key
     * less or equal to 0 means no expired time
    */
    private $ttl = 0;

    /**
     * the server list
    */
    private $servers = null;

    /**
     * the current redis connect instance
    */
    private $redis = null;
    
    /**
     * construct method
     * 
     * @param   $conf
    */
    public function __construct($conf)
    {
        if ( isset($conf['prefix']) ) $this->prefix = $conf['prefix'];
        if ( isset($conf['ttl']) && $conf['ttl'] > 0 ) {
            $this->ttl = $conf['ttl'];
        }

        if ( isset($conf['servers']) ) {
            $this->servers = &$conf['servers'];
        } else {
            $this->servers = array(
                array('localhost', 6379)
            );
        }
    }

    /**
     * internal method to do the basic partition
     * base on the bkdr hash value of the current key
    */
    protected function connect()
    {
        if ( $this->key == null ) {
            throw new Exception('Invoke the #setKey() to initialize the key first');
        }

        if ( $this->redis != null ) {
            return $this->redis;
        }

        $this->redis = new Redis();

        # for the multi server setting
        # calcule the hash value for the current key
        # then define the server config to connect to
        $count = count($this->servers);
        if ( $count == 1 ) {
            $server = $this->servers[0];
        } else {
            $server = $this->servers[bkdr_hash($this->key) % $count];
        }

        # connect to the selected redis server
        # check and do the authorized
        $this->redis->connect($server[0], $server[1]);
        if ( isset($server[2]) 
            && $this->redis->auth($server[2]) == false ) {
            throw new Exception('Authorized error with the specified password');
        }

        return $this->redis;
    }
    
    /**
     * set the current redis key 
     * 
     * @param   $key
     * @return  $this
    */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }
    
    /**
     * set the time to live in seconds
     *
     * @param   $ttl in seconds
     * @return  $this
    */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * get the value at the specified index
     *
     * @param   $index
     * @param   $callback
     * @param   Mixed String or false for failed or not exists
    */
    public function get($index, $callback=null)
    {
        $r = $this->connect()->lindex($this->key, $index);
        return ($callback != null && $r != false) ? $callback($r) : $r;
    }

    /**
     * set the value to the specified value at the specified index
     *
     * @param   $index
     * @param   $value
     * @return  boolean
    */
    public function set($index, $value)
    {
        $r = $this->connect()->lset($this->key, $index, $value);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * left push operation
     *
     * @param   $value
     * @return  Mixed false or the size of the list
    */
    public function lpush($value)
    {
        $r = $this->connect()->lpush($this->key, $value);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * left pop operation
     *
     * @param   $callback
     * @return  Mixed string or false for failed
    */
    public function lpop($callback=null)
    {
        $r = $this->connect()->lpop($this->key);
        return ($callback != null && $r != false) ? $callback($r) : $r;
    }

    /**
     * right push operation
     *
     * @param   $value
     * @return  Mixed false or the size of the list
    */
    public function rpush($value)
    {
        $r = $this->connect()->rpush($this->key, $value);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * right pop operaiton
     *
     * @param   $callback
     * @return  Mixed false or string
    */
    public function rpop($callback)
    {
        $r = $this->connect()->rpop($this->key);
        return ($callback != null && $r != false) ? $callback($r) : $r;
    }

    /**
     * return the size of the current list
     *
     * @return  integer
    */
    public function size()
    {
        return $this->connect()->llen($this->key);
    }

    /**
     * remove the current list specified by the current key
     *
     * @return  boolean
    */
    public function remove()
    {
        return $this->connect()->del($this->key);
    }

}
