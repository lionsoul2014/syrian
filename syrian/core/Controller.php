<?php if ( ! defined('APPPATH') ) exit('No Direct Access Allowed!');
/**
 * Opert Application Controller Class.
 * And this is the super class of the module controller class.
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

//-----------------------------------------------------------------
 
class Controller
{
    protected   $_view  = NULL;
	
	
	/**
	 * get the View component instance for the current request.
	 * 	it is an initialized opert.core.View instance.
	 *
	 * @param	$_timer	template compile cache time
	 * 			(0 for no cace, and -1 for permanent always).
	 * @param	resource	instance of opert/core/View.
	*/
	public function getView( $_timer = 0 )
    {
        if ( $this->_view == NULL )
		{
			Opert::import('core.View');
			$this->_view = new View($_timer);
			$_request = Opert::getRequest();
			$this->_view->_tpl_dir		= Opert::$_home . '/' .
				Opert::$_sysInfo['tpl_dir'] . '/' . $_request[0] . '/';
			$this->_view->_cache_dir	= Opert::$_home . '/' .
				Opert::$_sysInfo['cac_dir'] . '/temp/' . $_request[0] . '/';
		}
		return $this->_view;
    }
    
    /**
     * get the Cache component instance for current request.
     *      see the cache implements class in opert.lib.cache.dynamic .
     *
     * @return  ICache cache instance.
     * @see     opert/lib/cache/synamic/ICache
    */
    public function getCache() {}
    
    /**
     * get the Database component instance for the current request.
     *      it is an initialized opert.lib.db.Idb instance .
     *
     * @param   $idx  - connection index, use for cluster or distributed.
     * @return  resouce - instance of opert/lib/db/Idb
    */
    public function getDatabase( $idx = 0 ) {}
}
?>