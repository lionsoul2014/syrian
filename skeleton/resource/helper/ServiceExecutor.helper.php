<?php
/**
 * service executor helper
 *
 * @author  chenxin<chenxin619315@gmail.com>
*/

class ServiceExecutorHelper extends Helper
{
    /**
     * construct method
     *
     * @param    $conf
    */
    public function __construct($conf=NULL)
    {
        parent::__construct($conf);
    }

    /**
     * get the local executor
     *
     * @param   $sharding
     * @return  LocalExecutor
    */
    protected function createLocalExecutor($sharding=NULL)
    {
        //$conf = config('executor#dist_main');
        //if ( $conf == NULL ) return NULL;

        //import('service.GearmanExecutor');
        //return new GearmanExecutor($conf);

        import('service.LocalExecutor');
        return new LocalExecutor(NULL);
    }

    /**
     * get the Gearman executor
     *
     * @param   $sharding
     * @return  GearmanExecutor
    */
    protected function createGearmanExecutor($sharding)
    {
        $conf = config("executor#{$sharding}");
        if ( $conf == null ) return null;

        import('service.GearmanExecutor');
        return new GearmanExecutor($conf);
    }

    public function Test($input)
    {
        $async = isset($input[0]) ? $input[0] : false;
        return $async ? $this->createGearmanExecutor('dist_main') : $this->createLocalExecutor();
    }
    
}
?>
