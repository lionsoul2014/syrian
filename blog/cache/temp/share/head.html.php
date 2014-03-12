<div id="t-menu">
    <div class="t-box">
        <a href="<?php echo $this->_symbol["USR"]["url"]?>" class="t-link t-hover">首页</a> | 
        <a href="javascript:;" class="t-link t-hover">登陆</a> |
        <a href="javascript:;" class="t-link t-hover">收藏</a> |
        <a href="#" class="t-link">回到顶部</a>
    </div>
</div>
<div id="banner">
    <div class="ban-box">
        <div class="ban-txt">狮子的魂 - 平凡 | 执着</div>
        <div class="btx-box">#Sometime we could do better than we could imagine!#</div>
    </div>
</div>
<div  id="navi">
    <div id="navi-box">
        <?php foreach ( $this->_symbol['navi'] as $val ) {?>
        <a href="<?php echo Opert::makeStyleUrl($val["module"], $val["page"])?>"><?php echo $val["title"]?></a>
        <?php }?>
    </div>
</div>