<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
    "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
    <title><?php echo $this->_symbol["title"]?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->_symbol["USR"]["sta"]["css"]?>/article/list.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->_symbol["USR"]["sta"]["css"]?>/share/top-foot.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->_symbol["USR"]["sta"]["css"]?>/share/page.css"/>
</head>
<body>
<?php include $this->getIncludeFile('../share/head.html')?>
<div id="main">
    <div id="left">
        <h3 id="at-h3"><?php echo $this->_symbol["at_title"]?></h3>
        <ul id="at-list">
        <?php foreach ( $this->_symbol["type"] as $val ) {?>
            <li><a href="<?php echo $this->_symbol["SELF"]?>?tid=<?php echo $val["Id"]?>"><?php echo $val["title"]?></a></li>
        <?php }?>
        </ul>
    </div>
    
    <div id="right">
        <h3 id="aa-h3"><?php echo $this->_symbol["aa_title"]?></h3>
        <ul id="aa-list">
        <?php if ( $this->_symbol["data"] != FALSE ) {?>
            <?php foreach ( $this->_symbol['data'] as $val ) {?>
            <div class="list-item">
                <h3 class="li-title"><a href="<?php echo $val["url"]?>" target="_blank"><?php echo $val["title"]?></a></h3>
                <div class="li-info">作者：<?php echo $val["author"]?>, 建立时间：<?php echo $val["addtime"]?>, 下载量：<?php echo $val["hits"]?></div>
                <div class="li-brief"><?php echo $val["brief"]?></div>
            </div>
            <?php }?>
        <?php }?>
        </ul>
        
        <div id="page-box"><?php echo $this->_symbol["pagemenu"]?></div>
    </div>
</div>
<?php include $this->getIncludeFile('../share/foot.html')?>
</body>
</html>
