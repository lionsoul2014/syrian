<?php
if ( isset($_CACHE) ) {
	$_key = 'soft.about';
	$_ret = $_CACHE->get($_key, NULL, 2592000);
	if ( $_ret != FALSE ) 
	{
		echo $_ret;
		exit();
	}
}

$_soft = Opert::makeStyleUrl('soft', 'list');
$_about = <<<EOF
<p>关于狮子的魂：(The soul of a lion)<p>
<p>真名： 陈鑫, 湖南郴州人, 湖南理工学院退学学生。90后的一个普普通通的程序员，超级代码痴，严重代码洁癖，情商低至0，
	多个知名<a href="{$_soft}">开源软件</a>的作者。</p>
<p>技术研究领域：海量数据处理和高并发，NLP（重点：信息检索，中文分词，语音合成）。</p>
<p>爱好也是擅长：写代码，篮球，溜冰(极限/花样都ok啦)，I love Boxing too</p>
<p>常用博客：<a href="http://my.oschina.net/jcseg" target="_blank">http://my.oschina.net/jcseg</a></p>
<p>全部开源软件：<a href="http://git.oschina.net/lionsoul" target="_blank">http://git.oschina.net/lionsoul</a>
	或者<a href="http://code.google.com/p/cx-util/" target="blank">http://code.google.com/p/cx-util/</a></p>
<p>Sometimes you won't see it happen cause you have't try enough, You know you could do better!</p>
<h3>人生片段：</h3>
<p><b>2009年下半年：</b>进入湖南理工学院开始自己的大学生涯并且开始接触HTML/CSS/JS，发现自己对于计算机超级痴迷。</p>
<p><b>2010年上半年：</b>开始学习PHP和基本企业网站的搭建。</p>
<p><b>2010年的暑假：</b>岳阳宏图网络兼职一个月，刚起步的公司，没学到什么东西。</p>
<p><b>2010年下半年：</b>开始全面的学习php，包括：面向对象，正则，设计模式，Linux下开发。
	课堂上的时间让看完了图书馆TC312书架的80%的计算机图书</p>
<p><b>2011年上半年：</b>和大学同学，也是老乡阳建搬出了校园在学校旁边开办了“网络星空”网络工作室：主营：PHP培训，企业/门户网站建设。</p>
<p><b>2011年的暑假：</b>和工作室成员一起三个人(本人，阳建，张仁芳)从0开始开发了湖南理工学院的协会联盟，协会注册后就可以生成一个站点，用于协会发布信息，讨论交流，
	并且提供了类似博客一样的管理程序，是一个小复杂的系统，开发过程中学习实践了些许站点的性能提升方法。</p>
<p><b>2011年下半年：</b>陆续开发工作室的教程网，交友网以及工作室的官方网站。</p>
<p><b>2011年的寒假：</b>接做了合肥百贯福泰珠宝公司的C2C平台，功能完全类似淘宝。三个人(本人，阳建，张仁芳)一起做了1个半月，正如我预料的，网站下线了，
	这个时代电商的生存太难了。</p>
<p><b>2012年上半年：</b>完成了对上述公司的交接，明显的感觉到PHP在某些方面应用的局限性，开始疯狂的学习Java(先前已经有基础)，
	同时也开始研究中文分词和信息检索，同时完成了屏幕截图，远程控制，多媒体教学软件-jteach的开发，也是Jcseg的第一个版本的诞生时间段，
	也是大学疯狂挂课的开始。</p>
<p><b>2012年的暑假：</b>给工作室的人开设java课：完成了命令行学生管理系统，DClock桌面闹钟，Jchat多人聊天室的开发，
	基本让我对Java尤其是多线程，Swing有了比较深入的认识，同时开源了Jcseg的第一个版本。同时完成了我的退学大业。</p>
<p><b>2012年下班年：</b>深入的学习很明显的让我感觉到了底层和专业知识的不足，开始疯狂的学习C语言和《数据结构和算法》。
	同时和同班同学刘潇的工作室合并起名-畅想网络，做三件事：1. php/java培训, 2. 接做各种项目， 3. 产品研发。
	同时因为Jcseg的反馈良好激起了我继续开发的激情，然后给Jcseg发布了几个后续版本。</p>
<p><b>2012年的寒假：</b>想将分词移植到PHP下来，想过很多办法，最终的选择就是使用C重新开发一遍，一方面可以深入的应用C,
	一方面可以学习了解了PHP的扩展和内核，宅在家里接近一个月才完成了Friso以及其php扩展Robbe的开发，当然也开源了。</p>
<p><b>2013年上半年：</b>一方面忙于带培训班的学生，一方面不断的依据网友的反馈升级Jcseg和Friso/Robbe。依据自己的项目经验开发了一个
	PHP框架-Opert，哈，不过没有开源。</p>
<p><b>2013年的暑假：</b>6.27-6.30日，应CSDN的邀请去北京参加了开源世界开源中国第八次高峰论坛（ocow）。另外，哈，几个哥们又聚在一起了，给他们上了C语言的课程，
	同时完成了C语言扩展库-celib的开发，封装了常用的数据结构和算法。</>
<p><b>2013年下半年：</b>依据反馈升级Jcse/Friso/Robbe, 前些时间完善了celib加入了压缩/MD5等封装。后期深入的学习了Linux下C开发，
	多进程/线程，信号，IO, 异步IO，和Socket编程。依据所学的东西开发了一个LRU缓存框架-smcache，中途还做了一个坦克大战的小游戏tankwar。</p>
<p>会有更多的奇迹发生的。。。。。。。</p>
EOF;
$_VIEW->assoc('about', $_about);

//$_VIEW->display('list.html');
$_html = $_VIEW->getExecutedHtml('about.html');
if ( isset($_CACHE) ) $_CACHE->set($_key, NULL, $_html);
echo $_html;
?>
