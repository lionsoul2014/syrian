<?php
class ArticleModel extends SQLModel
{
    public function __construct()
    {
        parent::__construct();
        
        //load and create the database
        $this->db       = $this->getDatabase('Mysql', 'main');
        $this->tables   = Loader::config('table', 'db');

        //fetch the main table
        $this->table        = &$this->tables['user'];
        $this->primary_key  = 'Id';
    }
    
    public function getSoftList($_pageno = 1)
    {
        return array(
            array(
                'url'=>'http://code.google.com/p/jcseg',
                'title'=>'Java轻量级开源中文分词器-jcseg',
                'git'=>'http://git.oschina.net/lionsoul/jcseg',
                'brief'=>'Jcseg是使用Java开发的一个轻量级的中文分词器，使用流行的mmseg算法实现。1。目前最高版本：jcseg 1.9.3。兼容最高版本lucene-4.x和最高版本solr-4.x2。mmseg四种过滤算法，分词准确率达到了98.41%。' ),
            array(
                'url'=>'http://code.google.com/p/friso',
                'title'=>'C语言开源高性能中文分词器-friso',
                'git'=>'http://git.oschina.net/lionsoul/friso',
                'brief'=>'Friso是使用c语言开发的一个高性能跨平台的中文分词器，使用流行的mmseg算法实现。完全基于模块化设计和实现，可以很方便的植入到其他程序中，例如：MySQL，PHP等。支持对UTF-8/GBK编码的切分。目前最高版本：friso 1.6.0。' ),
            array(
                'url'=>'http://code.google.com/p/robbe',
                'title'=>'php开源高性能中文分词扩展-robbe',
                'git'=>'http://git.oschina.net/lionsoul/robbe',
                'brief'=>'Robbe是建立在Friso上的一个开源的高性能php中文分词扩展。目前最高版本：friso 1.5.0，支持对UTF-8/GBK编码的切分，mmseg四种过滤算法，分词准确率达到了98.41%。' ),
            array(
                'url'=>'http://code.google.com/p/jteach',
                'title'=>'java开源多媒体教学软件-jteach',
                'git'=>'http://git.oschina.net/lionsoul/jteach',
                'brief'=>'Jteach是使用java开发的一个跨平台的小巧，跨平台的教学软件。主要功能包括：1.屏幕广播。2.屏幕监视 + 控制 + 客户机广播。3.文件传输。4.远程命令执行(例如，关机命令等)。'),
            array(
                'url'=>'http://code.google.com/p/syrian',
                'title'=>'PHP开源轻量级开发框架-opert',
                'git'=>'#',
                'brief'=>'Opert是一个用php开发的轻量级框架，去除了大型框架的臃肿而带来的性能下降。1. 提供了基本的路由器，模板，控制器，模型封装。2. 提供了各种站点高性能方案封装，例如：文件/memcached缓存。3. 提供了常用的功能封装，例如：图片上传/压缩，数据过滤，邮件发送。'),
            array(
                'url'=>'https://code.google.com/p/cx-util/downloads/list',
                'title'=>'java开源AI坦克大战-tankwar',
                'git'=>'http://git.oschina.net/lionsoul/tankwar',
                'brief'=>'Tankwar是使用java开发的一个AI单机版的小游戏 (未使用任何游戏引擎)，和90经典版的坦克大战有些不同, 这里是纯坦克之间的战争, 英雄坦克并不用保护它的家。而是拼命的杀敌，保持自己的能量，在正确的时侯发送导弹，并且要想办法躲避智能坦克的追击和围攻。'
            ),
            array(
                'url'=>'http://code.google.com/p/cx-util/downloads/list',
                'title'=>'C语言扩展开发库-celib',
                'git'=>'http://git.oschina.net/lionsoul/celib',
                'brief'=>'Celib是使用ANSI C开发的一个扩展类库(c extend library)，包含了一些常用的数据结构和算法的封装，例如：hashmap, 链表，布隆过滤器，bitmap，动态数组，字符串操作API等。可以用于方便你的开发或者拿来学习。'
            )
        );
    }
}
?>
