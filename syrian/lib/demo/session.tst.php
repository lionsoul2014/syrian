<?php
//File session test program
header('Content-Type:text/html;charset=utf-8');
require(dirname(dirname(__FILE__)).'/session/SessionFactory.class.php');

$_conf = array(
    'save_path'        => dirname(__FILE__).'/session',
    'ttl'            => 86400,
);

$ses = SessionFactory::create('File', $_conf);
if ( ! $ses->has('uid') )
    $ses->set("uid", 10)->set('uagent', $_SERVER['HTTP_USER_AGENT'])->set('time', time());
else
{
    echo $ses->get('uid'),'，您上次在{'.date('Y-m-d H:i:s', $ses->get('time')).'}，通过'.$ses->get('uagent').'登录。';
}
?>
