<?php
/**
 * Common Time Util function class
 * 
 * @author chenxin <chenxin619315@gmail.com>
 */

//--------------------------------------------------------

class TimeUtil
{
    /**
     * convert the unix timestamp to seconds ago, hours ago, days ago
     *     month ago or years ago
     *
     * @param   $timer
     * @param   $ctime    current time 
     */
    public static function getTimeString( $timer, $ctime = NULL )
    {
        $t     = ($ctime == NULL ? time() : $ctime) - $timer;
        if ( $t < 0 ) return date('Y年m月d日', $timer);

        if ( $t < 5 )           return '刚刚';                            //just now
        if ( $t < 60 )          return $t.'秒前';                        //under one minuts
        if ( $t < 3600 )        return floor($t/60).'分钟前';            //under one hour
        if ( $t < 86400 )       return floor($t/3600).'小时前';            //under one day
        if ( $t < 2592000 )     return floor($t/86400).'天前';            //under one month
        if ( $t < 31104000 )    return date('m月d日', $timer);            //under one year
        return     date('Y年m月d日', $timer);
    }

    //get the current system time (microtime)
    public static function getMicroTime() 
    {
        list($msec, $sec) = explode(' ', microtime());    
        return ((float)$msec + (float)$sec);
    }

    /**
     * get the time period for a day
     * std: am(00-12:00), pm(12:00-24:00)
     * could be:
     * am: 06:00 - 12:00(less)
     * pm: 12:00 - 18:00(less)
     * ng: 18:00 - 24:00(less)
     *
     * @return    string
    */
    public static function getDayTimeKey($time=NULL)
    {
        if ( $time == NULL ) $time = time();

        $H = date('H', $time);
        if ( $H >= 0  && $H < 6  )    return 'bd';
        if ( $H >= 6  && $H < 12 )    return 'am';
        if ( $H >= 12 && $H < 18 )    return 'pm';
        //if ( $H >= 18 && $H < 24 )    return 'ng';
        return 'ng';
    }

}
