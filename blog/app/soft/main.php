<?php
/**
 * common interface for article module.
 * 		you can access the instance of the this class
 * 	throught $_COM at any logic script file.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class SoftController extends Controller
{
	private $_cache = NULL;
	private $_db = NULL;
	
	public function init()
	{
		Opert::import('lib.cache.agent.AgentCache');
		AgentCache::invoke(2592000);
		
		#$this->delMask(CTRL_LOAD_VIEW);
		#$this->addMask(CTRL_LOAD_CACHE);
		$this->setGVar('_TABLE', Opert::load('config.db.db-table'));
		#$this->_gvars['_TABLE'] = Opert::load('config.db.db-table');
		$this->addGVar('_PAGESIZE', 10);
		
		$view = $this->getView();
		$view->assign('navi', Opert::load('config.navi.navi-data'));
		$view->assign('title', '狮子的魂 - 平凡|执着');
		$view->assign('site', Opert::load('config.navi.site-data'));
	}
	
	public function getLogicScript( $_page )
	{
		$_dir = dirname(__FILE__);
		if ( strcmp($_page, 'about') == 0 ) return $_dir . '/about.php';
		return $_dir . '/list.php';
	}
	
	//@Override
	public function getCache()
	{
		if ( $this->_cache == NULL )
		{
			Opert::import('lib.cache.dynamic.CacheFactory');
			$this->_cache =  CacheFactory::create('file',
				array('cache_dir'=>Opert::$_sysInfo['cac_dir'].'/'));
		}
		return $this->_cache;
	}
	
	//@Override
	public function getDatabase( $idx = 0 )
	{
		if ( $this->_db == NULL )
		{
			Opert::import('lib.db.Dbfactory');
			$_host = Opert::load('config.db.db-host');
			$this->_db = Dbfactory::create('mysql', $_host[$idx]);
		}
		return $this->_db;
	}
}

return new SoftController();
?>
