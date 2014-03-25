<html>
<head>
    <title><?php echo $this->_symbol["title"]?></title>
    <meta name="Keywords" content=""/>
    <meta name="Description" content=""/>
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
