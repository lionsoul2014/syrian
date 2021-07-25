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

    /** @see SessionBase#_add($uid, $val, &$errno=self::OK) */
    protected function _add($uid, $val, &$errno=self::OK)
    {
        $r = $this->_mem->add($uid, $val, $this->_ttl);
        if ($r == true) {
            return true;
        }

        if ($this->_mem->getResultCode() == Memcached::RES_NOTSTORED) {
            $errno = self::CAS_FAILED;
        } else {
            $errno = self::OPT_FAILED;
        }
        
        return false;
    }

    /** @see SessionBase#_read($uid, $cas_token, &$exists) */
    protected function _read($uid, &$cas_token, &$exists=true)
    {
        if (defined('Memcached::GET_EXTENDED') == false) {
            $val = $this->_mem->get($uid, null, $cas_token);
            if ($val === false) {
                $val = '';
            }
        } else if (($r = $this->_mem->get(
            $uid, null, Memcached::GET_EXTENDED)) != false) {
            $val = $r['value'];
            $cas_token = $r['cas'];
        } else {
            $val = '';
        }

        # define the exists
        if ($this->_mem->getResultCode() == Memcached::RES_NOTFOUND) {
            $exists = false;
        } else {
            $exists = true;
        }

        # print("read: {err: {$this->_mem->getResultMessage()}, cas: {$cas_token}, val: {$val}}\n");
        return $val;
    }

    /** @see SessionBase#_update($uid, $val, $cas_token, &$errno=self::OK) */
    protected function _update($uid, $val, $cas_token, &$errno=self::OK)
    {
        # directly abort for no cas token
        if ($cas_token == null) {
            $errno = self::OPT_FAILED;
            return false;
        }

        # do the cas operation
        $r = $this->_mem->cas($cas_token, $uid, $val, $this->_ttl);
        # print("write: {err: {$this->_mem->getResultMessage()}, cas: {$cas_token}, val: {$val}}\n");
        if ($r == true) {
            return true;
        }

        # set the errno accoarding to the result code.
        if ($this->_mem->getResultCode() == Memcached::RES_DATA_EXISTS) {
            $errno = self::CAS_FAILED;
        } else {
            $errno = self::OPT_FAILED;
        }

        return false;
    }
    
    /** @see SessionBase#_delete($uid)*/
    protected function _delete($uid)
    {
        # print("delete: {$uid}\n");
        return $this->_mem->delete($uid);
    }
    
}
