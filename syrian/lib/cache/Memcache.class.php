<?php
/**
 * Memcache class implements ICache.
 *
 *
 * @author MonsterSlayer<slaying.monsters@gmail.com>
 * */

class Memcache implements ICache 
{
    private $_ttl 			= 0;
	private	$_prefix		= 'cache_';
    private $_mem           = NULL;

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
     *       'prefix'        => 'ses_'
     *   );
     *  
	 * @param	$conf
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
            && $conf['hash_strategy'] == 'consistent') 
        {
            $this->_mem->setOption(Memcached::OPT_DISTRIBUTION,
                     Memcached::DISTRIBUTION_CONSISTENT); 
        }

        if (isset($conf['prefix']) && $conf['prefix'] != '')
        {
            $this->_prefix = $conf['prefix'];
        }

        $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $this->_prefix);


        if (empty($this->_mem->getServerList())){
            $this->_mem->addServers($conf['servers']);
        } else {
           //throw new  Exception('Use Old Memcached server'); 
        }

		if ( isset( $conf['ttl'] ) )
			$this->_ttl	= $conf['ttl'];

    }


    public function get($_key) 
    {
        return $this->_mem->get($_key);
    }


    public function set($_key, $_data, $_ttl = NULL)
    {
        if ($_ttl == NULL) $_ttl = $this->_ttl;
        return $this->_mem->set($_sessid, $_data, $_ttl);
    }


    public function remove($key) 
    {
        $this->_mem->delete($_key); 
    }
}
?>
