<?php
require 'Filter.class.php';

//逐个验证
/*
$_name = Filter::get($_GET, "lang", array(OP_LATIN, OP_LIMIT(2, 6), OP_SANITIZE_TRIM | OP_SANITIZE_INT), $_errno);
if ( $_name === FALSE ) {
    echo '名字有问题.', $_errno,'<br />';
}

$_model = array(
    'title' => array(OP_STRING, OP_LIMIT(1, 60), NULL),
    'content'   => array(OP_STRING, OP_LIMIT(1, 255), NULL)
);
$_data = Filter::loadFromModel($_POST, $_model, $_errno);
if ( $_data === FALSE ) {
    echo '过滤失败. ';
    print_r($_errno);
}
*/

$_html = <<<EOF
普通script代码: <script>window.alert('Fuck You!!');</script>
script引用: <script language="javascript" src="xxxxx-func.js"></script>
html中的on事件: <img src="http://code.google.com/p/jcseg/logo?cct=1386494001" onclick="window.alert('Fuck You!')"/>
EOF;

echo '过滤前内容: <br />';
echo $_html, '<p />';

//Test the santize script.
echo '过滤后内容: <br />';
$_ret = Filter::filterVar($_html, array(OP_STRING, NULL, OP_SANITIZE_SCRIPT), $_errno);
if ( $_ret == FALSE ) print_r($_errno);
else echo $_ret;
?>