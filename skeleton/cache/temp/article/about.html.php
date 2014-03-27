<html>
<head>
    <title><?php echo $this->_symbol["site"]["title"]?></title>
    <meta name="Keywords" content="<?php echo $this->_symbol["site"]["keywords"]?>"/>
    <meta name="Description" content="<?php echo $this->_symbol["site"]["desc"]?>"/>
    <link rel="stylesheet" type="text/css" href="/static/style/dark/soft/about.css"/>
    <link rel="stylesheet" type="text/css" href="/static/style/dark/top-foot.css"/>
</head>
<body>
<?php include $this->getIncludeFile('../share/head.html')?>
<div class="main">
    <div class="c-box">
        <h3 class="p-h3">狮子的魂：</h3>
        <div id="list-box"><?php echo $this->_symbol["about"]?></div>
    </div>
</div>
<?php include $this->getIncludeFile('../share/foot.html')?>
</body>
</html>
