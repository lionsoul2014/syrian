<?php
/**
 * RBAC factory class 
 *
 * @author koma<komazhang@foxmail.com>
 * @date   2016/06/11
 *
 *
 * Database design:

CREATE TABLE IF NOT EXISTS `leray_privilege` (
`Id` int(10) unsigned NOT NULL COMMENT '权限id',
  `uri` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '0' COMMENT '地址标识',
  `remark` varchar(255) NOT NULL DEFAULT '0' COMMENT '备注',
  `cate_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '权限组id'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

ALTER TABLE `leray_privilege`
 ADD PRIMARY KEY (`Id`), ADD UNIQUE KEY `uri` (`uri`);

CREATE TABLE IF NOT EXISTS `leray_privilege_category` (
`Id` int(10) unsigned NOT NULL,
  `name` varchar(150) CHARACTER SET utf8 NOT NULL DEFAULT '0' COMMENT '分类名称'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0 ;

ALTER TABLE `leray_privilege_category` ADD PRIMARY KEY (`Id`);

CREATE TABLE IF NOT EXISTS `leray_role` (
`Id` int(10) unsigned NOT NULL COMMENT '角色id',
  `name` varchar(255) NOT NULL DEFAULT '0' COMMENT '角色名称',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '组id',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '2' COMMENT '角色类型（1-组长，2-成员）',
  `tag` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '0' COMMENT '角色标识'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='用户角色表' AUTO_INCREMENT=0 ;

ALTER TABLE `leray_role` ADD PRIMARY KEY (`Id`);

CREATE TABLE IF NOT EXISTS `leray_role_privilege` (
`Id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL COMMENT '角色id',
  `privilege_id` int(10) unsigned NOT NULL COMMENT '权限id'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

ALTER TABLE `leray_role_privilege`
 ADD PRIMARY KEY (`Id`), ADD UNIQUE KEY `role_id` (`role_id`,`privilege_id`);

CREATE TABLE IF NOT EXISTS `leray_group` (
`Id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '0' COMMENT '组名称',
  `tag` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '0' COMMENT '组标识'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

ALTER TABLE `leray_group` ADD PRIMARY KEY (`Id`);

CREATE TABLE IF NOT EXISTS `leray_group_privilege` (
`Id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL COMMENT '组id',
  `privilege_id` int(10) unsigned NOT NULL COMMENT '权限id'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

ALTER TABLE `leray_group_privilege`
 ADD PRIMARY KEY (`Id`), ADD UNIQUE KEY `group_id` (`group_id`,`privilege_id`);

CREATE TABLE IF NOT EXISTS `leray_admin_role` (
`Id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned NOT NULL COMMENT '管理员id',
  `role_id` int(10) unsigned NOT NULL COMMENT '角色id'
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

ALTER TABLE `leray_admin_role`
 ADD PRIMARY KEY (`Id`), ADD UNIQUE KEY `admin_id` (`admin_id`,`role_id`);

**/

Interface IRBAC
{
	/**
	 * store user privilege into SESSION
	 *
	 * @param array $userPrivilege
	 *
	 * @return boolean
	**/
	public function store($userPrivilege);

	/**
	 * check user privilege
	 *
	 * @param mixed string|array 
	 * @param array array('q1' => 'v1', 'q2' => 'v2', ..., 'op' => 'or|and') 
	 *
	 * @return boolean
	**/
	public function has($pattern, $param = null);

	/**
	 * destory user privilege info form SESSION
	 *
	**/
	public function destory();

	/**
	 * get store key
	 *
	 * @return string
	**/
	public function getKey();

	/**
	 * to do RBAC before check
	 *
	**/
	public function chkRbac();

	/**
	 * close RBAC check
	 *
	**/
	public function closeRbac();
}

class RbacFactory
{
	private static $instance = null;

	private function __construct()
	{
	}

	/**
	 * get RBAC instance by type
	 *
	 * @param string rbac store key unique identifying
	 * @param string rbac type name
	 *
	 * @return RBAC instance
	**/
	public static function getRbacHandler($userIdentifying, $type = 'session')
	{
		$type  = ucfirst($type);
		$class = "{$type}Rbac";

		if ( ! self::$instance instanceof $class ) {
			require dirname(__FILE__)."/{$class}.class.php";

			self::$instance = new $class($userIdentifying);
		}

		return self::$instance;
	}
}
