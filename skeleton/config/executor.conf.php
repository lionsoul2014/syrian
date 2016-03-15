<?php
/**
 * service executor configuration file
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

return array(
    //local executor
    'syn_local' => array(
    ),

    //distributed executor
    'dis_main'  => array(
        'servers' => array(
            array('127.0.0.1', 4730)
        ),

        //GEARMAN_CLIENT_GENERATE_UNIQUE | GEARMAN_CLIENT_NON_BLOCKING | 
        //GEARMAN_CLIENT_UNBUFFERED_RESULT | GEARMAN_CLIENT_FREE_TASKS
        'options' => NULL
    ),

    //distributed executor for user
    'dis_user'  => array(
        'servers' => array(
            array('127.0.0.1', 4730)
        ),
        'options' => NULL
    )
);
?>
