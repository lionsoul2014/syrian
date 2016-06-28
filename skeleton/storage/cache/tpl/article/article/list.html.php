<html>
<head>
    <title><?php echo $this->_symbol["site"]["title"]?></title>
    <meta name="Keywords" content="<?php echo $this->_symbol["site"]["keywords"]?>"/>
    <meta name="Description" content="<?php echo $this->_symbol["site"]["desc"]?>"/>
    <link rel="stylesheet" type="text/css" href="/static/style/dark/article/list.css"/>
    <link rel="stylesheet" type="text/css" href="/static/style/dark/top-foot.css"/>
</head>
<body>
<?php include $this->getIncludeFile('share/head.html')?>
<div class="main">
    <div class="c-box">
        <h3 class="p-h3">开源软件：</h3>
        <div id="list-box">
        <?php if ( $this->_symbol["data"] != FALSE ) {?>
            <?php foreach ( $this->_symbol['data'] as $val ) {?>
            <div class="list-item">
                <h3 class="li-title"><a href="<?php echo $val["url"]?>" target="_blank"><?php echo $val["title"]?></a></h3>
                <div class="li-brief"><?php echo $val["brief"]?></div>
                <div class="li-box">
                    <a href="<?php echo $val["url"]?>" target="_blank" class="lb-index button-link">软件官方网站</a>
                    <a href="<?php echo $val["git"]?>" target="_blank" class="lb-git button-link">查看Git源码托管</a>
                </div>
            </div>
            <?php }?>
        <?php }?>
        </div>
    </div>
</div>
<?php include $this->getIncludeFile('share/foot.html')?>
</body>
</html>
