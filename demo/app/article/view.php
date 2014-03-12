<?php
$_view = $this->getView(0);
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ( $id == 0 ) $this->redirect('article', 'list');

/*
$_cache = $this->getFileCache();
$_key = 'article.view';
$_widget = $_cache->get($_key, $id, 0);
if ( $_widget != FALSE ) {
    echo 'use cache <br />';
    echo $_widget;
    exit();
}
*/

Opert::import('db.Dbfactory');
//load database information
$_host = Opert::load('config.db.db-host');
$_table = Opert::load('config.db.db-table');

$_query = 'select * from '.$_table['article'].' where Id = '.$id;
$db = Dbfactory::create('mysql', $_host[0]);

$_ret = $db->getOneRow($_query);
$_view->assoc('article', $_ret);

$_html = $_view->getExecutedHtml('view.html');
//$_cache->set($_key, $id, $_html);
echo $_html;
?>