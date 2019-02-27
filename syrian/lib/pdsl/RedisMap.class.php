<?php
/**
 * Redis map implementation with IMap interafce implemented
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

class RedisMap implements IMap
{

    /**
     * the auto insert prefix
    */
    private $prefix = null;
    
    /**
     * the key for the current map
    */
    private $key = null;

    /**
     * the TTL for the current key
     * less or equal to 0 means no expired time
     *
     * @Note All the update operations will reset TLL of the current key
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
     * return the size of the current map
     *
     * @return  integer
    */
    public function size()
    {
        return $this->connect()->llen($this->key);
    }

    /**
     * remove the current map specified by the current key
     *
     * @return  boolean
    */
    public function remove()
    {
        return $this->connect()->del($this->key);
    }

    /**
     * return all the keys in the current map
     *
     * @return  Array or false for failed
    */
    public function keys()
    {
        return $this->connect()->hKeys($this->key);
    }

    /**
     * return all the values in the current map
     *
     * @return  Array or false for failed
    */
    public function values()
    {
        return $this->connect()->hVals($this->key);
    }

    /**
     * get the value associated with the specified key
     * and invoke callback on it if defined.
     *
     * @param   $key
     * @param   $callback
     * @return  Mixed string for false for failed
    */
    public function get($key, $callback=null)
    {
        $r = $this->connect()->hGet($this->key, $key);
        return ($callback != null && $r != false) ? $callback($r) : $r;
    }

    /**
     * Retrieve the values associated to the specified fields
     *
     * @param   $key_arr
     * @param   $callback
     * @return  Array
    */
    public function mget($key_arr, $callback=null)
    {
        $a = $this->connect()->hMGet($this->key, $key_arr);
        if ( $callback == null ) {
            return $a;
        }

        $r = array();
        foreach ( $a as $v ) {
            $r[] = $callback($v);
        }

        return $r;
    }

    /**
     * set the value associated with the specified key
     *
     * @param   $key
     * @param   $value
     * @return  Mixed LONG 1 if value did not exist and added successfully
     *  0 if the value was already present and was replaced, False for failed
    */
    public function set($key, $value)
    {
        $r = $this->connect()->hSet($this->key, $key, $value);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * add a value to the map associated with a specified key
     * Only if this field is not already exist
     *
     * @param   $key
     * @param   $val
     * @return  Mixed TRUE if field was set or false if it was present.
    */
    public function setNx($key, $value)
    {
        $r = $this->connect()->hSetNx($this->key, $key, $value);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * fills in a whole hash, Non-string values will be convert to string.
     * NULL will be convert to empty string.
     *
     * @param   $key
     * @param   $val_arr
     * @return  bool
    */
    public function mset($key, $val_arr)
    {
        $r = $this->connect()->hMSet($this->key, $key, $val_arr);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * Increase the value of a member by a given amount
     * associated with the specified key.
     *
     * @param   $key
     * @param   $i_val
     * @return  LONG the new value
    */
    public function incBy($key, $i_val)
    {
        $r = $this->connect()->HIncrBy($this->key, $key, $i_val);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * Increase the value of a member by a given float value
     *  associated with the specified key.
     *
     * @param   $key
     * @param   $f_val
     * @return  FLOAT the new value
    */
    public function incByFloat($key, $f_val)
    {
        $r = $this->connect()->HIncrByFloat($this->key, $key, $f_val);
        if ( $this->ttl > 0 && $r != false ) {
            $this->connect()->expire($this->key, $this->ttl);
        }

        return $r;
    }

    /**
     * Verify if the specified member exists in a key.
     *
     * @param   $key
     * @return  BOOL: true if the member exists in the map or false.
    */
    public function exists($key)
    {
        return $this->connect()->hExists($this->key, $key);
    }

    /**
     * Remove a value from the hash stored at key
     *
     * @param   $key
     * @return  LONG - the number of deleted keys, 0 if the key does not exists
     *      FALSE if the key is not a hash
    */
    public function del($key)
    {
        return $this->connect()->hDel($this->key, $key);
    }

    /**
     * close the connection
    */
    public function close()
    {
        if ( $this->redis != null ) {
            $this->redis->close();
            $this->redis = null;
        }
    }

}
