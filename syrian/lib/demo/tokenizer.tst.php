<?php
require dirname(__FILE__) . '/Tokenizer.class.php';

$_tokenizer = new Tokenizer('/php/com-ext/pcseg/conf.hash.lex');
$_text = "后来老师又换了座位，也是和一个成绩很好的男生坐在一起，我们也都开着彼此的玩笑，但是，却似乎感觉，上课的时候总有一双眼睛在盯着我，有时候，老师在黑板上抄题，允许学生可以到前面坐，他也总是跑到我们的座位旁，和我们一起，边聊天，边抄题。但是，由于留级生太多，我们压力真的太大，曾经有那么一段时间，我真的很迷茫，特别是物理，真的很差，老是拖我后退，对于他的难，我也觉察到了，所以有一天晚上，下了晚自习，同学们都走了，我写了一张纸条，大概是鼓励他的话，环顾四周，发现没人，偷偷地塞在了他的书桌里，熄了灯，一个人走在了黑暗中。";
//$_text = "friso高性能中文分词，中文分词性能的革命性提升。";
//$_text = "歧义和同义词:研究生命起源，混合词: 做B超检查身体，本质是X射线，单位和全角: 2009年８月６日开始大学之旅，中文数字: 四分之三的人都交了六十五块钱班费，那是一九九八年前的事了，四川麻辣烫很好吃，五四运动留下的五四精神。笔记本五折包邮亏本大甩卖。人名识别: 我是陈鑫，也是jcesg的作者，三国时期的诸葛亮是个天才，我们一起给刘翔加油，罗志高兴奋极了因为老吴送了他一台笔记本。配对标点: 本次『畅想杯』黑客技术大赛的得主为电信09-2BF的张三，奖励C++程序设计语言一书和【畅想网络】的『PHP教程』一套。特殊字母: 【Ⅰ】（Ⅱ），英文数字: bug report chenxin619315@gmail.com or visit http://code.google.com/p/jcseg, 15% of the day's time i will be there.特殊数字: ① ⑩ ⑽ ㈩.";

$s_time = timer();
$_ret = $_tokenizer->split($_text);
$e_time = timer();
foreach ( $_ret as $_val )
    echo $_val . '/ ';
echo '<br />Done, Cost: ' . ( $e_time - $s_time ) . "sec <br />";
    
function timer() {
    list($msec, $sec) = explode(' ', microtime());    
    return ((float)$msec + (float)$sec);
}
?>
