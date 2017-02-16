<?php
/**
 * login Controller
 * 
 * @author chenxin<chenxin619315@gmail.com>
*/

import('StringUtil');
import('image.QRcode');

 //------------------------------------------------------
 
class LoginController extends C_Controller
{    
    public function actionIndex($input, $output)
    {
        $uuid   = StringUtil::genGlobalUid();
        $qrFile = "uploadfiles/qrcode/{$uuid}.png";
        QRCode::png(
            "http://www.syrian.org/login/confirm?uuid={$uuid}",
            $qrFile,
            'L',
            8,
            2
        );

        $cache = helper('MainCache#LoginLock', $uuid);
        $cache->set(json_encode(array(
            'status'  => 0,
            'confirm' => false,
            'user_id' => 7
        )));

        return view('login/index.html', array(
            'uuid' => $uuid,
            'qrcode_src' => "{$this->conf->url}/$qrFile"
        ));
    }

    public function actionConfirm($input)
    {
        $uuid = $input->getUID('uuid');
        if ( $uuid == false ) {
            return null;
        }

        //check and update the lock status
        $expired = false;
        $cache = helper('MainCache#LoginLock', $uuid);
        $cc = $cache->get(null, 'json_decode');
        if ( $cc == false ) {
            $expired = true;
        } else {
            $cc->status = 1;
            $cache->set(json_encode($cc));
        }

        return view('login/confirm.html', array(
            'uuid' => $uuid,
            'expired' => $expired
        ));
    }

    public function actionSuccess($input)
    {
        $uuid = $input->getUID('uuid');
        if ( $uuid == false ) {
            return null;
        }

        $cache = helper('MainCache#LoginLock', $uuid);
        $cc = $cache->get(null, 'json_decode');
        if ( $cc == false || $cc->status != 1 ) {
            return null;
        }

        $cc->status = 2;
        $cache->set(json_encode($cc));
        return '登陆成功！';
    }

    public function actionConnect($input)
    {
        $uuid = $input->getUID('uuid');
        if ( $uuid == false ) {
            return json_view(STATUS_INVALID_ARGS, 'Invalid Arguments');
        }

        set_time_limit(0);
        $cache = helper('MainCache#LoginLock', $uuid);

        $i = 0;
        while ( $i < 24 ) {
            $json = $cache->get(null, 'json_decode');
            if ( $json == null ) {
                return json_view(STATUS_INVALID_ARGS, 'Invalid Arguments');
            }

            if ( $json->status == 1 ) {
                if ( $json->confirm == false ) {
                    $json->confirm = true;
                    $cache->set(json_encode($json));
                    break;
                }
            } else if ( $json->status == 2 ) {
                $cache->remove();   //remove the cache lock data

                //@Note: get the user data with $json->user_id
                return json_view(STATUS_OK, array(
                    'code' => 2,
                    'data' => array(
                        'user_id' => $json->user_id,
                        'nickname' => 'lionsoul',
                        'head_img' => 'http://www.weitoutiao.com/favicon.ico'
                    )
                ));
            }

            usleep(500000); //sleep for 0.5 second
            $i++;
        }

        return json_view(STATUS_OK, array(
            'code' => $json->status,
            'data' => null
        ));
    }

}
?>
