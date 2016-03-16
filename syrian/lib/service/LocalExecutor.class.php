<?php
/*
 * service local executor class.
 * 
 * @author  chenxin<chenxin619315@gmail.com>
 */

 //--------------------------------------

class LocalExecutor
{
    /**
     * service path and Object cacher
    */
    private static $POOL = array();

    /**
     * construct method
     *
     * @param   $conf
    */
    public function __construct($conf)
    {
    }
    
    /**
     * service router:
     * get the service through the server path like 'stream.stat.access';
     *
     * @param   $path
     * @return  Service object
    */
    protected function getService($path)
    {
        $path = str_replace('/', '.', strtolower($path));
        if ( isset(self::$POOL[$path]) ) {
            return self::$POOL[$path];
        }

        $part = explode('.', $path);
        if ( count($part) < 2 ) {
            return NULL;
        }

        $method  = array_pop($part);
        $pathArr = array();
        $nameArr = array();
        foreach ( $part as $val ) {
            $pathArr[] = $val;
            $nameArr[] = ucfirst($val);
        }

        //define the class file
        $clsFile = SR_SERVICEPATH.implode('/', $pathArr).'/main.php';
        $clsName = implode('',  $nameArr).'Service';

        if ( ! file_exists($clsFile) ) {
            return NULL;
        }

        require $clsFile;

        if ( ! class_exists($clsName) ) {
            return NULL;
        }

        $obj = new $clsName();
        $ret = array($method, $obj);
        self::$POOL[$path] = $ret;

        return $ret;
    }

    /**
     * execute the specifield service.
     *
     * @param   $serv_path
     * @param   $args Array
     * @return  Mixed
    */
    public function execute($serv_path, $args, $asyn=true, $priority=NULL)
    {
        $servInfo = $this->getService($serv_path);
        if ( $servInfo == NULL ) {
            throw new Exception("Cannot found service specifiled with {$serv_path}\n");
        }

        //invoke the run entrance method of the service
        $servObj = $servInfo[1];
        return $servObj->run($servInfo[0], $args);
    }
}
?>
