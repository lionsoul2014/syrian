<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
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
}
?>