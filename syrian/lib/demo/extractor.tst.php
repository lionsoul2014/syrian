<?php
header('Content-Type: text/html; charset=UTF-8');
include ('Extractor.class.php');

set_time_limit(0);

$_urls = array(
    'http://www.chinanews.com/gj/2014/03-21/5980876.shtml',
    'http://ent.sina.com.cn/y/2010-04-18/08332932833.shtml',
    'http://ent.sina.com.cn/y/2014-03-20/16164114680.shtml',
    'http://ent.qq.com/a/20140321/021207.htm',
    'http://music.yule.sohu.com/20140322/n397034300.shtml',
    'http://news.hit.edu.cn/articles/2014/03-20/03174234.htm',
    'http://hnist.cn/html/news/2014/0321/10240.html',
    'http://code.google.com/p/jcseg',
    //'http://www.alibaba.com/help/safety_security/class/buying/find_supplier/003.html',
    'http://www.361blog.com/seo/433.html',
    'http://hi.baidu.com/earthsearch/item/470cc59ecfde12f0281647b9'
);

$extractor = new Extractor();

//date_default_timezone_set('PRC');
//echo date('Y-m-d H:i:s', 1395468180);
/*
$_str = '<a href="http://www.chinanews.com/">首页</a>　<a href="http://news.chinanews.com/">新闻</a>　<a href="http://www.chinanews.com/china.shtml">国内</a>　<a href="http://world.chinanews.com/">国际</a>
    <a href="http://mil.chinanews.com/">军事</a>　<a href="http://www.chinanews.com/society.shtml">社会</a>　<a href="http://www.chinanews.com/df/">地方</a>　<a href="http://www.chinanews.com/fz/">法治</a>
  <a href="http://bbs.chinanews.com/">社区</a>　<a href="http://bbs.chinanews.com/index.php">论坛</a>　<a href="http://bbs.chinanews.com/forum-80-1.html">曝料</a>
    <a href="http://t.chinanews.com/">微博</a>　<a href="http://bbs.chinanews.com/forum.php?gid=53">休闲</a>　<a href="http://bbs.chinanews.com/home.php">空间</a>
  <a href="http://www.chinaqw.com/">侨网</a><a href="http://www.chinanews.com/hb/" style="padding-left:9px;">华文报摘</a><a href="http://www.chinanews.com/gangao/" style="padding-left:9px;">港澳</a><a href="http://www.chinanews.com/zgqj/">侨界</a><a href="http://www.chinanews.com/huaren/" style="padding-left:5px;">华人</a><a href="http://www.chinanews.com/hwjy/" style="padding-left:5px;">华教</a><a href="http://www.chinanews.com/taiwan/" style="padding-left:5px;">台湾</a>
  <a href="http://finance.chinanews.com/" >财经</a><a href="http://fortune.chinanews.com/" style="padding-left:8px;">金融</a><a href="http://stock.chinanews.com/" style="padding-left:8px;">证券</a><a href="http://auto.chinanews.com/" style="padding-left:8px;">汽车</a><a href="http://it.chinanews.com/" style="padding-left:8px;">I T</a><a href="http://house.chinanews.com/">房产</a><a href="http://energy.chinanews.com/" style="padding-left:16px;">能源</a><a href="http://game.chinanews.com/" style="padding-left:16px;">游戏</a><a href="http://shop.chinanews.com/" style="color:#ff0000;padding-left:16px;" >商城</a>
  <a href="http://cul.chinanews.com/">文化</a>　<a href="http://ent.chinanews.com/">娱乐</a>　<a href="http://www.chinanews.com/sports/index.shtml">体育</a>
    <a href="http://edu.chinanews.com/">教育</a>　<a href="http://health.chinanews.com/">健康</a>　<a href="http://life.chinanews.com/">生活</a>
  <a href="http://www.chinanews.com/shipin/">视频</a>　<a href="http://interview.chinanews.com/">访谈</a>　<a href="http://www.chinanews.com/piaowu/index.html">演出</a>
    <a href="http://photo.chinanews.com/">图片</a>　<a href="http://photolib.chinanews.com/">图库</a>　<a href="http://user.chinanews.com/">供稿</a>
  <a href="http://www.ecns.cn/">English</a>';
echo $extractor->getLinkWordsRate($_str);
*/

foreach ( $_urls as $_val )
{
    $extractor->reset();
    $extractor->setUrl($_val);
    $extractor->run();
    
    echo '<h3>'.$extractor->getTitle().'</h3>', "\n";
    echo '<div>'.$extractor->getText().'</div>', "\n";
    echo '<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>', "\n\n";
}
?>
