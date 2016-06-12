<?php
/**
 * SESSION RBAC manage class 
 *
 * @author koma<komazhang@foxmail.com>
 * @date   2016/05/24
 * 
**/

class SessionRbac implements IRBAC
{
	/**
	 * prefix key for SESSION key
	 *
	**/
	private $prefix = 'rbac_privilege_';
	/**
	 * user identifying
	 *
	**/
	private $userIdentifying = '';
	/**
	 * config whether to do RBAC check
	 *
	**/
	private $chkRbac = true;

	public function __construct($userIdentifying)
	{
		$this->userIdentifying = $userIdentifying;
	}

	/**
	 * store user privilege into SESSION
	 *
	 * @param array $userPrivilege
	 *
	 * @return boolean
	**/
	public function store($userPrivilege)
	{
		//store user privilege into session
		if ( !empty($userPrivilege) ) {
			$key = $this->getKey();
			$_SESSION[$key] = serialize($userPrivilege);
		}
		unset($userPrivilege);

		return true;
	}

	/**
	 * check user privilege
	 *
	 * @param mixed string|array 
	 * @param array array('q1' => 'v1', 'q2' => 'v2', ..., 'op' => 'or|and') 
	 *
	 * @return boolean
	**/
	public function has($pattern, $param = null)
	{
		//this is a must step for check whether 
		//we need to do RBAC check
		if( ! $this->chkRbac() ) return true;

		$key    = $this->getKey();
		$flag   = false;
		$isarr  = false;
		$arrlen = 0;
		$chkp   = false;
		$op     = '';
		$total  = 0;

		if ( !isset($_SESSION[$key]) ) return $flag;

		if ( is_array($pattern) ) {
			$isarr  = true;
			$arrlen = count($pattern);
		}

		if ( is_string($pattern) && strpos($pattern, '?') > 0 ) {
			$_tmp     = explode('?', $pattern);
			$pattern  = $_tmp[0];
			
			if ( $param != null && is_array($param) ) {
				$chkp  = true;
				$op    = 'and';
				if ( isset($param['op']) ) {
					$op = $param['op'];
					unset($param['op']);
				}
				$total = count($param);
			}
			unset($_tmp);
		}

		$userPrivilege = unserialize($_SESSION[$key]);
		
		//step1 check uri path
		foreach ( $userPrivilege as $uri ) {
			if ( $isarr ) {
				foreach ( $pattern as $p ) {
					if ( ($_pos = strpos($p, '?')) > 0 ) {
						$p = substr($p, 0, $_pos);
						unset($_pos);
					}

					if ( preg_match("#{$p}#", $uri) > 0 ) {
						$flag = true;
						break;
					}
				}
			} else {
				if ( preg_match("#{$pattern}#", $uri) > 0 ) {
					//step2 check uri param
					if ( $chkp ) {
						$query = array();
						$_flag = false;

						if ( ($_pos = strpos($uri, '?')) > 0 ) {
							parse_str(substr($uri, $_pos+1), $query);
							unset($_pos);
						}

						if ( $op == 'or' ) {
							foreach ( $param as $p => $v ) {
								if ( isset($query[$p]) && $query[$p] == $v ) {
									$_flag = true;
									break;
								}
							}		
						} else if ( $op == 'and' ) {
							$_count = 0;

							foreach ( $param as $p => $v ) {
								if ( isset($query[$p]) && $query[$p] == $v ) {
									++$_count;
								}
							}

							if ( $total == $_count ) {
								$_flag = true;
							}
						}

						if ( $_flag ) {
							$flag = true;
						}
						unset($query, $_flag);
					} else {
						$flag = true;
					}

					if ( $flag ) break;
				}
			}
		}
		unset($userPrivilege, $key, $pattern, $param);

		return $flag;
	}

	/**
	 * get store key
	 *
	 * @return string
	**/
	public function getKey() {
		return $this->prefix.$this->userIdentifying;
	}

	/**
	 * to do RBAC before check
	 *
	**/
	public function chkRbac()
	{
		return $this->chkRbac;
	}

	/**
	 * close RBAC check
	 *
	**/
	public function closeRbac()
	{
		$this->chkRbac = false;
	}

	/**
	 * destory user privilege info form SESSION
	 *
	**/
	public function destory()
	{
		$key = $this->getKey();
		if ( isset($_SESSION[$key]) ) unset($_SESSION[$key], $key);
	}
}
