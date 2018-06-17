<?php
/**
 * list implementation base on Redis
 *
 * @author chenxin<chenxin619315@gmail.com>
*/
class RedisList implements IList
{
    
    private $key = null;

    /**
     * the TTL for the current key
     * less or equal to 0 means no expired time
    */
    private $ttl = 0;

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
        $this->redis = new Redis();
    }

    /**
     * internal method to do the basic partition
     * base on the bkdr hash value of the current key
    */
    protected function connect($conf, $key)
    {
        $this->redis = new Redis();
        $this->redis->connect(
        );
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

    public function get($index)
    {
    }

    public function set($index, $value);
    public function lpush($value);
    public function lpop();
    public function rpush($value);
    public function rpop();
    public function size();
    public function remove ();
}
