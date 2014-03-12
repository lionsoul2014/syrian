<div id="banner">PHP轻量级高性能开发框架 - Opert</div>
<div  id="navi">
    <ul id="navi-box">
        <?php foreach ( $this->_symbol['navi'] as $val ) {?>
        <li><a href="<?php echo Opert::makeStyleUrl($val["module"], $val["page"])?>"><?php echo $val["title"]?></a></li>
        <?php }?>
    </ul>
</div>