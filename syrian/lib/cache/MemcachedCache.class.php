<?php
/**
 * Memcached class 
 *
 *
 * @author MonsterSlayer<slaying.monsters@gmail.com>
 * */

class MemcachedCache implements ICache
{
    private $_ttl       = 0;
    private $_baseKey   = '';
    private $_fname     = '';
    private $_mem       = NULL;
    private $_key       = '';
    private $_prefix    = ''; //prefix used inter the memcached
    private $_hash      = Memcached::HASH_DEFAULT;
    public static $_hash_opts = array(
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
     * @param    $conf
     */
    public function __construct( &$conf )
    {
        if (!isset($conf['servers']) || empty($conf['servers'])){
           throw new Exception('Memcached server should not be empty'); 
        }
            
        $this->_mem  = new Memcached();

        // hash distribute strategy, 
        // default: Memcached::DISTRIBUTION_MODULA
        if (isset($conf['hash_strategy']) 
            && $conf['hash_strategy'] == 'consistent') {
            $this->_mem->setOption(Memcached::OPT_DISTRIBUTION,
                     Memcached::DISTRIBUTION_CONSISTENT); 
            $this->_mem->setOPtion(Memcached::OPT_LIBKETAMA_COMPATIBLE, TRUE);
        }

        if (isset($conf['hash']) 
            && $conf['hash'] != 'default' 
            && array_keys(self::$_hash_opts, $conf['hash'])) {
            $this->_hash = self::$_hash_opts[$conf['hash']];
            $this->_mem->setOption(Memcached::OPT_HASH, $this->_hash); 
        }


        if (isset($conf['prefix']) && $conf['prefix'] != '') {
            $this->_prefix = $conf['prefix'];
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $this->_prefix);
        }

        if (isset($conf['ttl']) &&($ttl = intval($conf['ttl'])) > 0) {
            $this->_ttl = $ttl;
        }

        $servers = $this->_mem->getServerList();
        if (empty($servers)){
            $this->_mem->addServers($conf['servers']);
        } else {
           //throw new  Exception('Use Old Memcached server'); 
        }
    }

    public function baseKey($bk= NULL){
        if ($bk != NULL) $this->_baseKey = $bk;
        $this->_key = $this->_baseKey . $this->_fname;
        return $this;
    }


    public function fname($fname= NULL){
        if ($fname != NULL) $this->_fname = $fname;

        $this->_key = $this->_baseKey . $this->_fname;
        return $this;
    }

    //set the global time to live seconds
    public function setTtl($_ttl = NULL)
    {
        if( ( $_ttl = intval($_ttl)) < 0)
            $_ttl = 0;
        
        $this->_ttl = $_ttl;
        return $this;
    }

    // we don't need the $_time param, just for implements ICache
    public function get($_time = NULL) {
        if ($this->_key == '' ) return false;

        return $this->getByKey($this->_key);
    }


    public function getByKey($_key) 
    {
        return $this->_mem->get($_key);
    }


    public function set($_data, $_ttl = NULL) {
        if ($this->_key == '' 
            && empty($_data)) return false;

        return $this->setByKey($this->_key, $_data, $_ttl);
    }

    public function setByKey($_key, $_data, $_ttl = NULL)
    {
        if ( $_ttl === NULL || ($_ttl = intval($_ttl) ) < 0 ) 
            $_ttl = $this->_ttl;
        return $this->_mem->set($_key, $_data, $_ttl);
    }


    public function remove(){
       return $this->removeByKey($this->_key); 
    }


    public function removeByKey($_key) 
    {
        $this->_mem->delete($_key); 
    }


    /**
     * implements functions
     *
     * */
    public function factor($_factor) 
    {
        return $this;
    }
}
?>
