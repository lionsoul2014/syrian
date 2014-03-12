<?php
/**
 * In Opert, we consider some specified pages in a module.
 * like classify article-list and article-view to article module.
 * 	and the MCOM compoent offer the common interface that
 * 		all the pages shared in the module.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

//controller auto load components mask
defined('CTRL_LOAD_VIEW')   or  define('CTRL_LOAD_VIEW',  1 << 0);
defined('CTRL_LOAD_CACHE')  or  define('CTRL_LOAD_CACHE', 1 << 1);
defined('CTRL_LOAD_DB')     or  define('CTRL_LOAD_DB',    1 << 2);
defined('CTRL_LOAD_ALL')    or  define('CTRL_LOAD_ALL',
                            CTRL_LOAD_VIEW | CTRL_LOAD_CACHE | CTRL_LOAD_DB);

abstract class Controller
{
    protected   $_cmask = CTRL_LOAD_VIEW;
    protected   $_gvars = array();
    protected   $_view  = NULL;
    
    /**
     * module common initialize method.
    */
    public function init()
    {
	
    }
    
    /**
     * component mask load check method.
     *
     * @param   $_mask  component mask.
     * @return  bool    true for load and false for not.
    */
    public function getMask( $_mask )
    {
        return ( ($this->_cmask & $_mask) != 0 );
    }
    
    /**
     * component mask add method.
     *
     * @param   $_mask  component mask.
    */
    public function addMask( $_mask )
    {
        $this->_cmask |= $_mask;
    }
    
    /**
     * delete the component load mask.
     *
     * @param   $_mask  component mask
    */
    public function delMask( $_mask )
    {
        $this->_cmask &= ~$_mask;
    }
	
    /**
     * get the logic processor script.
     * 	often the script file located at the same directory with the MCOM.
     *
     * @param	$_page	the current request page.
     * @return	string	the <b>absolute</b> path of the script file.
    */
    public abstract function getLogicScript( $_page );
	
	
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
	
	
    /**
     * add a new global variable.
     *
     * @param	$_name	name of the variable (Mayby the type like _NAME)
     * @param	$_value	value of the variable.
    */
    public function addGVar( $_name, $_value )
    {
	$this->_gvars[$_name] = &$_value;
    }
    
    /**
     * set the value of the specfied globals variale.
     *
     * @param	$_name	name of the exists variable.
     * @param	$_value	new value of the variable.
    */
    public function setGVar( $_name, $_value )
    {
	if ( isset( $this->_gvars[$_name] ) )
	    $this->_gvars[$_name] = &$_value;	
    }

    /**
     * return the global variables that is going to
     * 		register to the scprit file.
     *
     * @return	Array (or NULL)
    */
    public function gVars()
    {
	return empty($this->_gvars) ? NULL : $this->_gvars;
    }
}
?>