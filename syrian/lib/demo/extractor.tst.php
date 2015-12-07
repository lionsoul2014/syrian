<?php
header('Content-Type: text/html; charset=UTF-8');

$_show = false;
if ( isset($_GET['_act']) && $_GET['_act'] == 'go' )
{
    require (dirname(dirname(__FILE__)) . '/nlp/Extractor.class.php');
    
    $_url = trim($_GET['url']);
    $_threshold = intval($_GET['threshold']);
    $_linkrates = floatval($_GET['linkrates']);
    
    if ( $_url != '' )
    {
        $extractor = new Extractor(EXTRACTOR_ALL);
        //$extractor->reset();
        $extractor->setUrl($_url);
        $extractor->config(array(
                'threshold'        => $_threshold,
                'linkrate'        => $_linkrates
            ));
        $extractor->run();
        
        $_show = true;
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>Text Extractor Test program</title>
    <style>
    body {text-align: center;}
    #main {margin: auto;text-align:left;width: 1000px;}
    #input {overflow: hidden;zoom:1;border-bottom: 1px solid #CCC;}
    #input div {padding: 5px 0px;}
    #url {height: 26px;line-height: 26px;width: 500px;background: #FFF;border: 1px solid #CCC;
        color:#555;padding: 3px;}
    .input-item {border: 1px solid #CCC;height: 22px;line-height: 22px;background: #FFF;width: 100px;
        color:#555;padding: 3px;}
    #box {}
    </style>
</head>
<body>
<div id="main">
    <div id="input">
        <form name="test" action="#" method="get">
            <div><input type="text" name="url" value="<?=$_show?$_url:''?>" id="url"/><input type="submit" value="Go" /></div>
            <div>threshold: <input type="text" name="threshold" value="<?=$_show?$_threshold:'225'?>" class="input-item"/></div>
            <div>linkrates: <input type="text" name="linkrates" value="<?=$_show?$_linkrates:'0.30'?>" class="input-item"/></div>
            <input type="hidden" name="_act" value="go"/>
        </form>
    </div>
    <?php
    if ( $_show )
    {
    ?>
    <div id="box">
        <h1>TITLE: <?=$extractor->getTitle()?></h1>
        <div><?=str_replace("\n", '<p/>', $extractor->getText())?></div>
    </div>
    <?php
    }
    ?>
</div>
</body>
</html>
