<?php
require 'Verifycode.class.php';

$codeimage = Verifycode::getInstance();

$codeimage->generate()->setWidth(130)->setHeight(50)->setX(20)->setY(25)->setFontsize(20)->show('png');

$_SESSION['code'] = $codeimage->getCode();

/**
 * 1. 创建一个画布
 * |- imagecreate
   |- imagecreatetruecolor *
   
   
 * 2. 绘制图形
    (1). 为画布资源分配颜色
        |-imagecolorallocate *
    (2). 使用已分配的颜色绘制图形
        |- imagefill
        |- imagestring
        |- imagettftext *

 * 3. 输出图形 *
   |- imagejpeg 
   |- imagetgif
   |- imagepng 
   |- imagewbmp
   
 * 4. 销毁画布资源 *
   |- imagedestroy
 */
 //

 /*
 $_imagesrc = imagecreatetruecolor( 200, 100  );
 //分配颜色
 $_bg = imagecolorallocate( $_imagesrc, 0, 0, 0 );
 $_color = imagecolorallocate( $_imagesrc, 255, 200, 0 );
 
 $_linecolor = imagecolorallocate( $_imagesrc, 00, 200, 0 );
 //填充
 imagefill( $_imagesrc, 0, 0, $_bg );
 imagearc ( $_imagesrc, 100, 50, 50, 50, 0, 0, $_color);
 imageline( $_imagesrc, 50 , 20, 150, 80, $_linecolor );
 imagesetpixel( $_imagesrc, 160, 50, $_color );
 

 //imagestring( $_imagesrc, 5, 20, 20, $_str,  $_color);
 //imagestringup( $_imagesrc, 5, 150, 80, $_str,  $_color );
$_font = 'font/simsun.ttc';
 
$_font2 = 'font/GB2312.ttf';
 
imagettftext( $_imagesrc, 16, -30, 20, 60, $_color, $_font, '你' );
 
imagettftext( $_imagesrc, 24, 30, 60, 50, $_color, $_font2, '好' );
 
 
imagegif( $_imagesrc );
imagedestroy( $_imagesrc );
 
*/
 
 
 
 
/*
$_image = imagecreatetruecolor(200,200);//创建一个画布

$_bg = imagecolorallocate( $_image, 0, 0, 0 );
$_color1 = imagecolorallocate( $_image, 200, 0, 0 );
$_color2 = imagecolorallocate( $_image, 0, 200, 0 );
$_color3 = imagecolorallocate( $_image, 0, 0, 200 );

imagefill( $_image, 0, 0, $_bg );

for( $i = 60; $i > 50; $i-- ){
    imagefilledarc( $_image , 50, $i, 100, 50, -160, 30, $_color1, IMG_ARC_PIE );
    imagefilledarc( $_image , 50, $i, 100, 50, -270, -160, $_color2, IMG_ARC_PIE );
    imagefilledarc( $_image , 50, $i, 100, 50, 30, -270, $_color3, IMG_ARC_PIE );   
}


imagepng ( $_image ) ;
*/
 
?>