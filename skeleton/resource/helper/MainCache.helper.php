<?php
/**
 * main cacher helper
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

import('CacheHelper', false);

class MainCacheHelper extends CacheHelper
{
    //-----------README-----------------------
    //@Note: for every method blow:
    //all the method of the cacher #baseKey(),#factor(),#fname(),#setTtl()
    //    MUST be invoked

    /**
     * index data cache
     *
     * @param   $input
     * @return  Object ICache
    */
    public function LoginLock($input)
    {
        $uuid = $input[0];
        return self::getCacher('Memcached')
            ->baseKey('login.lock')->factor(null)->fname($uuid)->setTtl(3600);
    }

}
?>
