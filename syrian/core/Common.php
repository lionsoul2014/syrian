<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Common functions for each request.
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

//---------------------------------------------------------------

/**
 * Import class file from the specified path
 * The function will check script file $_path.class.php first
 * 	and then $_path.php
 *
 * @param	$_class - style like 'lib.db.Dbfactory'
 * @param	#_inc	If $_inc is TRUE check the opert/lib  or check APPHOME/lib
 * @return	bool	true for loaded successfuly and false for not
 */
if ( ! function_exists('import') )
{
	function import($_class, $_inc = true)
	{
		//All the loaded classes.
		static $_classes = array();
		
		if ( isset($_classes[$_class]) ) return;
		
		//Look for the class in the SYSPATH/lib folder if $_inc is TRUE
		//Or check the APPPATH/lib 
		$_dir = (($_inc) ? BASEPATH : APPPATH) . LIBDIR . '/';
		$_dir .= str_replace('.', '/', $_class);
		
		foreach( array($_dir . '.class.php', $_dir . '.php') as $_cfile )
		{
			if ( file_exists($_cfile) )
			{
				require $_cfile;
				$_classes[$_class] = true;
				return true;
			}
		}
		
		//exit('Syrian: Unable to load class ' . $_class);
		return false;
	}
}


/**
 * function to load data from the specified file
 * 	and return the return of the included file as the final result
 *
 * @param	$_dfile		data file(style like parent.dir.file) without php extension
 * @param	$_inc		search files in framework lib dir? true for yes
 * @return	mixed(Array, Object, Bool)
 */
if ( ! function_exists('load') )
{
	function load( $_cfile, $_inc = false )
	{
		//make the included file name
		$_file = (($_inc) ? BASEPATH : APPPATH);
		$_file .= str_replace('.', '/', $_cfile) . '.php';
		
		if ( file_exists($_file) )
		{
			return include $_file;
		}
		
		//throw new Exception('No such file or directory');
		return false;
	}
}
?>