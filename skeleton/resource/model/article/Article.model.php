<?php

import('model.C_Model');

class ArticleModel extends C_Model
{
    public function __construct()
    {
        parent::__construct();
        
        //load and create the database
        $this->db = self::getDatabase('Mysql', 'main');

        //fetch the main table
        $this->table        = 'table_name';
        $this->primary_key  = 'Id';
    }
    
    public function getSoftList($_pageno = 1)
    {
        return array(
            array(
                'url'=>'http://git.oschina.net/lionsoul/jcseg',
                'title'=>'Java轻量级开源中文分词器-jcseg',
                'git'=>'http://github.com/lionsoul2014/jcseg',
                'brief'=>'Jcseg是基于mmseg算法的一个轻量级开源中文分词器，同时集成了关键字提取，关键短语提取，关键句子提取和文章自动摘要等功能，并且提供了最新版本的lucene, solr, elasticsearch的分词接口。'
            ),
            array(
                'url'=>'http://git.oschina.net/lionsoul/friso',
                'title'=>'C语言开源高性能中文分词器-friso',
                'git'=>'http://github.com/lionsoul2014/friso',
                'brief'=>'Friso是使用C语言开发的一款高性能中文分词器，使用流行的mmseg算法实现。完全基于模块化设计和实现，可以很方便的植入到其他程序中，例如：MySQL，PHP等。同时支持对UTF-8/GBK编码的切分。'
            ),
            array(
                'url'=>'http://git.oschina.net/lionsoul/ip2region',
                'title'=>'ip到地名的映射库-ip2region',
                'git'=>'http://github.com/lionsoul2014/ip2region',
                'brief'=>'准确率99.9%的ip到地名的映射库，0.0x毫秒级查询，数据库文件大小只有3.5M，提供了java, php, c, python查询绑定。妈妈再也不同担心我的ip地址定位！'
            ),
            array(
                'url'=>'http://code.google.com/p/syrian',
                'title'=>'PHP开源轻量级开发框架-syrian',
                'git'=>'#',
                'brief'=>'Syrian是一个用php开发的轻量级框架，去除了大型框架的臃肿而带来的性能下降。1. 提供了基本的路由器，模板，控制器，模型封装。2. 提供了各种站点高性能方案封装，例如：文件/memcached缓存。3. 提供了常用的功能封装，例如：图片上传/压缩，数据过滤，邮件发送。'
            ),
            array(
                'url'=>'http://git.oschina.net/lionsoul/tankwar',
                'title'=>'java开源AI坦克大战-tankwar',
                'git'=>'https://github.com/lionsoul2014/tankwar',
                'brief'=>'Tankwar是使用java开发的一个AI单机版的小游戏 (未使用任何游戏引擎)，和90经典版的坦克大战有些不同, 这里是纯坦克之间的战争, 英雄坦克并不用保护它的家。而是拼命的杀敌，保持自己的能量，在正确的时侯发送导弹，并且要想办法躲避智能坦克的追击和围攻。'
            ),
            array(
                'url'=>'http://git.oschina.net/lionsoul/celib',
                'title'=>'C语言扩展开发库-celib',
                'git'=>'https://github.com/lionsoul2014/celib',
                'brief'=>'Celib是使用ANSI C开发的一个扩展类库(c extend library)，包含了一些常用的数据结构和算法的封装，例如：hashmap, 链表，布隆过滤器，bitmap，动态数组，字符串操作API等。可以用于方便你的开发或者拿来学习。'
            )
        );
    }
}
?>
