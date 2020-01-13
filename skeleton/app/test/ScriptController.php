<?php
/**
 * test script controller
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

class ScriptController extends S_Controller
{
    public function actionIndex()
    {
        return array(
            'common/JTE.js',
            'common/jquery.1.11.js',
            'common/Ajax.class.js'
        );
    }
}