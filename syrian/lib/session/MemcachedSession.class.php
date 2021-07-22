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

        if (isset($conf['hash']) && isset(self::$_hash_opts[$conf['hash']])) {
            $this->_mem->setOption(Memcached::OPT_HASH, self::$_hash_opts[$conf['hash']]); 
        }

        if (isset($conf['prefix'])) {
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $conf['prefix']);
        } else {
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, '_sess_');
        }

        $servers = $this->_mem->getServerList();
        if (empty($servers)) {
            $this->_mem->addServers($conf['servers']);
        }

        parent::__construct($conf);
    }

    /** @see SessionBase#_read($uid)*/
    protected function _read($uid, &$cas_token=null)
    {
        if (defined(Memcached::GET_EXTENDED) == false) {
            $val = $this->_mem->get($uid, null, $cas_token);
        } else if (($r = $this->_mem->get(
            $uid, null, Memcached::GET_EXTENDED)) != false) {
            $val = $r['value'];
            $cas_token = $r['cas'];
        } else {
            $val = '';
        }

        # print("read: {$val}\n");
        return $val == false ? '' : $val;
    }
    
    /** @see SessionBase#_write($uid, $val, $cas_token)*/
    protected function _write($uid, $val, $cas_token)
    {
        # print("write: {$val}\n");
        return $this->_mem->set($uid, $val, $this->_ttl);
    }
    
    /** @see SessionBase#_destroy($uid)*/
    protected function _destroy($uid)
    {
        # print("delete: {$uid}\n");
        return $this->_mem->delete($uid);
    }
    
}
