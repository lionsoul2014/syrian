<?php
define('__BASE__', dirname(dirname(__FILE__)));

//代理缓存,压缩传输,多文件盒1(减少http请求次数)
invoke(3600*24*365);
header('Content-Type:text/css');

ob_start('ob_gzip');
require __BASE__.'/article/list.css';
require __BASE__.'/share/top-foot.css';
require __BASE__.'/share/page.css';
ob_end_flush();

function ob_gzip( $_buffer ) {
    $_buffer = gzencode($_buffer, 9);
    header("Content-Encoding: gzip");  
    header("Vary: Accept-Encoding");  
    header("Content-Length: ".strlen($_buffer));
	return $_buffer;
}

function invoke( $_cache_time = 0 ) {
    if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] )
        && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) + $_cache_time > time() ) {
        header('HTTP/1.1 304');
        exit();
    }
    //send the last modified time
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time() + $_cache_time) . ' GMT');
    header('Cache-Control: max-age=' . $_cache_time);
    //header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $_cache_time) . ' GMT');
}
?>