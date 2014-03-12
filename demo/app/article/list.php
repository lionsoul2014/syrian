<?php
$_pageno	= isset($_GET['pageno']) ? $_GET['pageno'] : 1;
$_tid		= isset($_GET['tid']) ? $_GET['tid'] : 0;

if ( isset($_CACHE) ) {
	$_key = 'article.list.'.$_tid;
	$_ret = $_CACHE->get($_key, $_pageno, 3600);
	if ( $_ret != FALSE ) {
		echo 'use cache <br />';
		echo $_ret;
		exit();
	}
}

//import model class
Opert::import('lib.page.Page');
$_VIEW->assign('title', 'Opert高性能php框架 - 狮子的魂');
$_VIEW->assign('at_title', '软件分类');
$_VIEW->assign('aa_title', '软件列表');
$_type = array(
	array('Id'=>1, 'title'=>'Java相关软件'),
	array('Id'=>2, 'title'=>'c语言相关软件'),
	array('Id'=>3, 'title'=>'php相关软件')
);
$_VIEW->assoc('type', $_type);

$_list = array(
	array(
        'id'    => '1',
        'url'=>'http://code.google.com/p/jcseg',
        'title'=>'java开源中文分词器-jcseg', 'author'=>'狮子的魂',
		'addtime'=>'2012-05-18 17:25:12', 'hits'=>'3120', 'brief'=>'jcseg是使用Java开发的一个中文分词器，使用流行的mmseg算法实现。1。目前最高版本：jcseg 1.8.0。 兼容最高版本的lucene，(1.7.0及后的版本，词库发生了很大变化，和之前的版本不再兼容)。2。mmseg四种过滤算法，分词准确率达到了98.41%。' ),
	array(
        'id'    => '2',
        'url'=>'http://code.google.com/p/jteach',
        'title'=>'java开源多媒体教学软件-jteach', 'author'=>'狮子的魂',
        'addtime'=>'2012-04-16 10:12:25', 'hits'=>'320', 'brief'=>'jteach是使用java开发的一个小巧，跨平台的教学软件。一。主要功能：1.屏幕广播。2.屏幕监视 + 控制 + 客户机广播。3.文件传输4.远程命令执行(例如，关机命令)'),
	array(
        'id'    => '3',
        'url'=>'http://code.google.com/p/friso',
        'title'=>'c语言开源高性能中文分词器-friso', 'author'=>'狮子的魂',
        'addtime'=>'2012-12-28 22:25:15', 'hits'=>'1001', 'brief'=>'friso是使用c语言开发的一个中文分词器，使用流行的mmseg算法实现。完全基于模块化设计和实现，可以很方便的植入到其他程序中，例如：MySQL，PHP等。1。目前最高版本：friso 0.1，只支持UTF-8编码。【源码无需修改就能在各种平台下编译使用，加载完20万的词条，内存占用稳定为14M。】。' ),
	array(
        'id'    => '4',
        'url'=>'http://code.google.com/p/robbe',
        'title'=>'php开源高性能中文分词扩展-robbe', 'author'=>'狮子的魂',
		'addtime'=>'2013-01-07 09:25:18', 'hits'=>'310', 'brief'=>'robbe是建立在friso中文分词上的一个高性能php中文分词扩展。了解friso1.目前最高版本：friso 0.1，【源码无需修改即可在各平台下编译运行】2.mmseg四种过滤算法，分词准确率达到了98.41%。' ),
	array(
        'id'    => '5',
        'url'=>'http://code.google.com/p/syrian',
        'title'=>'Opert高性能framework', 'author'=>'狮子的魂',
		'addtime'=>'2013-03-20 14:30:12', 'hits'=>'998', 'brief'=>'Syrian是一个用php开发的高性能内容管理系统，由畅想网络研发中心研发和实现。目前最高版本0.1-beta。'),
);
$_VIEW->assoc('data', $_list);

$_page = new Page(1000, 10, $_pageno);
//$_sql .= ' limit '.$_page->getOffset().', '.$_size;
//$_page->limit($_sql);
$_VIEW->assign('pagemenu', $_page->show('tid='.$_tid, UI_SHOP_STYLE));

//$_VIEW->display('list.html');
$_html = $_VIEW->getExecutedHtml('list.html');
if ( isset($_CACHE) ) $_CACHE->set($_key, $_pageno, $_html);
echo $_html;
?>