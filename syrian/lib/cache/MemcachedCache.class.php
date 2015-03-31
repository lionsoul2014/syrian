<?php
/**
 * Memcached class 
 *
 *
 * @author MonsterSlayer<slaying.monsters@gmail.com>
 * */

class MemcachedCache implements ICache
{
    private $_ttl 			= 0;
	private	$_baseKey		= '';
	private	$_fname  		= '';
    private $_mem           = NULL;
    private $_key           = '';
    private $_prefix        = ''; //prefix used inter the memcached

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
            $this->_mem->setOption(Memcached::OPT_PREFIX_KEY, $this->_prefix);
        }


        if (empty($this->_mem->getServerList())){
            $this->_mem->addServers($conf['servers']);
        } else {
           //throw new  Exception('Use Old Memcached server'); 
        }

		if ( isset( $conf['ttl'] ) )
			$this->_ttl	= $conf['ttl'];

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


    // we don't need the $_time param, just for implements ICache
    public function get($_time) {
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
        if ($_ttl == NULL) $_ttl = $this->_ttl;
        return $this->_mem->set($_key, $_data, $_ttl);
    }


    public function remove(){
       return $this->removeByKey($this->_key); 
    }


    public function removeByKey($key) 
    {
        $this->_mem->delete($_key); 
    }


    /**
     * implements functions
     *
     * */
    public function factor($_factor) 
    {
        return true;
    }
}
?>
