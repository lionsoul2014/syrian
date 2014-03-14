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
    protected   $input  = NULL;			//request input
	protected   $output = NULL;			//request output
	protected   $uri  	= NULL;			//request uri
	protected   $view  	= NULL;			//request view
	protected	$loader	= NULL;			//loader
	
	public function __construct()
	{
		
	}
	
	/**
	 * 
	*/
	public function run()
	{
		//user logic file to handler the request
		$_logicFile = $this->uri->page . '.logic.php';
		if ( file_exists($_logicFile) )
			include $_logicFile;
		else
			redirect('/error/404', 'src_page=' . $this->uri->url);
	}
}
?>