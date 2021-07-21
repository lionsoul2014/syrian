<?php
/**
 * user level session handler class base on memcached
 *    
 * @author chenxin<chenxin619315@gmail.com>
*/

class MemcachedSession extends SessionBase
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

    private $_mem = null;

    /**
     * construct method to initialize the class
     * 
     * demo config data:
     * $_conf = array(
     *     'servers'       => array(
     *         array('localhost', 11211, 60), // host, port, weight
     *         array('localhost', 11212, 40),
     *     ),
     *     'ttl'           => 60, // time to live
     *     // default: standard,  consistent was recommended,
     *     // for more infomation,  search 'consistent hash'
     *     'hash_strategy' => 'consistent',
     *     'hash'          => 'default', // hash function,  empty for default
     *     'prefix'        => 'ses_'
     * );
     *  
     * @param   $conf
     */
    public function __construct($conf)
    {
        parent::__construct($conf);

        if (! isset($conf['servers']) || empty($conf['servers'])) {
           throw new Exception('Memcached server should not be empty'); 
        }

        $this->_mem = new Memcached();

        // Memcached hash distribute strategy, 
        // default to Memcached::DISTRIBUTION_MODULA
        if (isset($conf['hash_strategy'])) {
            switch ($conf['hash_strategy']) {
            case 'consistent':
                $this->_mem->setOption(Memcached::OPT_DISTRIBUTION,
                    Memcached::DISTRIBUTION_CONSISTENT); 
                $this->_mem->setOPtion(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                break;
            }
        }

        if (isset($conf['hash'])) {
            $hash = self::$_hash_opts[$conf['hash']];
            $this->_mem->setOption(Memcached::OPT_HASH, $hash); 
        }

        if (isset($conf['prefix'])) {
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $conf['prefix']);
        }

        $servers = $this->_mem->getServerList();
        if (empty($servers)) {
            $this->_mem->addServers($conf['servers']);
        }
    }

    /** @see SessionBase#_read($uid)*/
    protected function _read($uid)
    {
        $str = $this->_mem->get($uid);
        # print("read: {$str}\n");
        return $str == false ? '' : $str;
    }
    
    /** @see SessionBase#_write($uid, $str)*/
    protected function _write($uid, $str)
    {
        if (strlen($str) < 1) {
            $this->_mem->delete($uid);
            return true;
        }

        # print("write: {$str}\n");
        return $this->_mem->set($uid, $str, $this->_ttl);
    }
    
    /** @see SessionBase#_destroy($uid)*/
    protected function _destroy($uid)
    {
        $this->_mem->delete($uid);
    }
    
}
