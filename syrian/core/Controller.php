<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Opert Application Controller Class.
 * And this is the super class of the module controller class.
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
class Controller extends stdClass
{
	public   	$uri  	= NULL;			//request uri
    public   	$input  = NULL;			//request input
	public   	$output = NULL;			//request output
	
	/**
	 * Construct method to create new instance of the controller
	 *
	 * @param	$uri
	 * @param	$input
	 * @param	$output
	*/
	public function __construct()
	{
	}
	
	/**
	 * the entrance of the current controller
	 * default to invoke the uri->page.logic.php to handler
	 * 	the request, you may need to rewrite this method to self define
	 *
	 * @access	public
	*/
	public function run()
	{
		//user logic file to handler the request
		$_logicScript = $this->uri->page . '.logic.php';
		if ( file_exists($_logicScript) )
			include $_logicScript;
		else
			$this->uri->redirect('/error/404');
	}
	
	/**
     * get the View component instance for the current request
     * 	it is an initialized lib.HtmlView instance
     *
     * @param	$_timer	template compile cache time
     * 			(0 for no cace, and -1 for permanent always)
     * @param	resource	instance of syrian/lib/HtmlView
    */
	public function getHtmlView( $timer = 0 )
    {
		Loader::import('HtmlView');
		$view  = new HtmlView($timer);
		$view->_tpl_dir		= APPPATH.SR_TEMPDIR.'/'.$this->uri->module.'/';
	    $view->_cache_dir	= APPPATH.SR_CACHEDIR.'/temp/'.$this->uri->module.'/';
		
		return $view;
    }
	
	/**
     * get the Database component instance for the current reques
     *      it is an initialized syrian/lib/db/Idb instance 
     *
     * @param   $idx  - connection index, use for cluster or distributed.
     * @return  resouce - instance of syrian/lib/db/Idb
    */
    public function getDatabase( $idx = 'main' )
	{
		Loader::import('Dbfactory', 'db');
		$_host = Loader::config('hosts', 'db');
		return Dbfactory::create('mysql', $_host[$idx]);
	}
}
?>