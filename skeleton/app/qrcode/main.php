<?php
/**
 * QrcodeController
 * 
 * @author chenxin<chenxin619315@gmail.com>
*/

import('image.QRcode');

 //------------------------------------------------------
 
class QrcodeController extends C_Controller
{    
    public function actionIndex($input, $output)
    {
        $output->setHeader('Content-Type', 'image/png');
        QRCode::png(
            "http://www.weitoutiao.com/user/home",
            false,
            'L',
            8,
            2
        );
    }

}
?>
