<?php
/**
 * session manager class for user center.
 *
 * @author chenxin <chenxin619315@gmail.com>
*/
class session
{
	public static function init()
	{
		session_start();
	}
	
	public static function register( $_uid, $_uname, $_umail )
	{
		$_SESSION['ls_uid'] = &$_uid;
		$_SESSION['ls_uname'] = &$_uname;
		$_SESSION['ls_umail'] = &$_umail;
		$_SESSION['ls_uagent'] = $_SERVER['HTTP_USER_AGENT'];
		return true;
	}
	
	public static function check()
	{
		if ( ! isset($_SESSION['ls_uid']) || $_SESSION['ls_uid'] == ''
			|| ! isset($_SESSION['ls_uname']) || $_SESSION['ls_uname'] == ''
			|| ! isset($_SESSION['ls_umail']) || $_SESSION['ls_umail'] == ''
			|| ! isset($_SESSION['ls_uagent']) || $_SESSION['ls_uagent'] == '' )
			return false;
		return true;
	}
	
	public static function destroy()
	{
		$_SESSION['ls_uid'] = '';
		$_SESSION['ls_uname'] = '';
		$_SESSION['ls_umail'] = '';
		$_SESSION['ls_uagent'] = '';
	}
	
	public static function getUID()
	{
		return $_SESSION['ls_uid'];
	}
	
	public static function getUName()
	{
		return $_SESSION['ls_uname'];
	}
	
	public static function getUMail()
	{
		return $_SESSION['ls_umail'];
	}
}
?>