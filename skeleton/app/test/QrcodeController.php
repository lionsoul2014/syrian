<?php
/**
 * QrcodeController
 * 
 * @author chenxin<chenxin619315@gmail.com>
*/

import('image.QRcode');


/**
 * Class QrcodeController
 * @author yangjian
 */
class QrcodeController extends C_Controller
{
    /**
     * @param Input $input
     * @param Output $output
     */
    public function show($input, $output)
    {
        $text = $input->get("text", null, "http://www.weitoutiao.com/user/home");
        $output->setHeader('Content-Type', 'image/png');
        QRCode::png(
            $text,
            false,
            'L',
            8,
            2
        );
    }

}