<?php
/**
 * common controller for sketelon
 *
 * @author 	chenxin<chenxin619315@gmail.com>
*/

 //--------------------------------------------------

class C_Controller
{
	//view cache time
	protected 	$vc_time 	= 0;

	public function __construc( )
	{
		parent::__construct();
	}
	
	public function run()
	{
		$this->view 	= $this->getView($this->vc_time);
		$this->sysconf 	= Loader::config('sys');
	}
	
	/**
     * get the View component instance for the current request
     * 	it is an initialized lib/view/IView instance
     *
     * @param	$_timer	template compile cache time
     * 			(0 for no cace, and -1 for permanent always)
     * @param	resource	instance of syrian/lib/view/IView
    */
	public function getView( $timer = 0 )
    {
		Loader::import('ViewFactory', 'view');
		
		$_conf = array(
			'cache_time'	=> $timer,
			'tpl_dir'		=> SR_VIEWPATH .$this->uri->module.'/',
			'cache_dir'		=> SR_CACHEPATH.'temp/'.$this->uri->module.'/'
		);
		
		//return the html view
		return ViewFactory::create('Html', $_conf);
    }
}
?>