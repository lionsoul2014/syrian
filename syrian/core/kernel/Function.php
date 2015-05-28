<?php if ( ! defined('BASEPATH') ) exit('No Direct Access Allowed!');
/**
 * Application common functions
 *
 * @author	chenxin <chenxin619315@gmail.com>
 * @link	http://www.lionsoul.org/syrian
 */

 //-----------------------------------------------------------------
 
/**
 * global run time resource
*/
if ( ! function_exists('_G') )
{
	function _G($key, $val=NULL)
	{
		static $_GRE = array();

		if ( $val == NULL )
		{
			return isset($_GRE["{$key}"]) ? $_GRE["{$key}"] : NULL;
		}

		$_GRE["{$key}"] = &$val;
		return true;
	}
}
?>
