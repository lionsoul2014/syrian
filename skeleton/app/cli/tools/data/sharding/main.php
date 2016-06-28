<?php
/**
 * ShardingController manager class
 *
 * @author  chenxin <chenxin619315@gmail.com>
*/

//------------------------------------

class ShardingController extends CliController
{
    private $debug  = false;

    public function __construct()
    {
        parent::__construct();
    } 

    /**
     * controller entrace method you could use the default one
     *      by just invoke parent::run() or write you own implementation
     *
     * @see Controller#run()
    */
    public function run()
    {
        parent::run();

        $this->debug = $this->input->getBoolean('debug', false);

        if ( strncmp($this->uri->page, 'convert', 7) == 0 ) $this->_convert();
    }

    /**
     * convert the triditional table to sharding tables
     * copy the data, mainly actually!
     *
     * @date: 2016-02-26
    */
    public function _convert()
    {
        $originalModelPath = $this->input->get('originalModel');
        $shardingModelPath = $this->input->get('shardingModel');
        if ( $originalModelPath == false 
            || $shardingModelPath == false ) {
            exit("missing originalModel or shardingModel\n");
        }

        $originalModel = self::loadModelFromPath($originalModelPath);
        $shardingModel = self::loadModelFromPath($shardingModelPath);

        if ( $this->debug ) $originalModel->setDebug(true);
        if ( $this->debug ) $shardingModel->setDebug(true);

        $primary_key = $originalModel->getPrimaryKey();
        $startPos    = $this->input->getInt('startPos', 0);
        $trafficNum  = $this->input->getInt('trafficNum', 300);
        $removeOld   = $this->input->getBoolean('removeOld', false);
        $interval    = $this->input->getInt('interval', 0);
        $intelMode   = $this->input->getBoolean('intelMode', false);
        $ondup       = $this->input->get('ondup');

        $Id = $startPos;
        for ( ; ; ) {
            if ( $this->process_state == CLI_PROC_EXIT ) {
                break;
            }

            /*
             * check and do the intelligent logic
            */
            if ( $intelMode == true ) {
                $H = date('H');
                if ( $H >= 0 && $H < 1 ) {
                    $interval = 3000;
                } else if ($H >= 1 && $H <= 6) {
                    $interval = 5;
                } else if ( $H <= 18 ) {
                    $interval = 2000;
                } else if ( $H <= 24 ) {
                    $interval = 3000;
                }
            }

            //--------------------------------------------------

            $ret = $originalModel->getList(
                array('*'),
                array($primary_key => ">{$Id}"),
                array($primary_key => 'asc'),
                "0,{$trafficNum}"
            );

            if ( $ret == false ) {
                break;
            }

            //copy the data
            $idstring = NULL;
            if ( $removeOld == true ) {
                $idstring = Util::implode($ret, $primary_key, ',', false);
            }

            echo "+-Try to copy the data ... ";

            /**
             * we will generate univesal unqiue identifier
             * so, unset the primary_key here
            */
            $data = array();
            foreach ( $ret as $val ) {
                unset($val[$primary_key]);
                $data[] = $val;
            }

            $rows = $shardingModel->batchAdd($data, $ondup);
            echo " --[{$rows}] rows copied\n";

            if ( $removeOld == true ) {
                echo "+-Try to delete the copied records ... ";
                $_where = array($primary_key => "in({$idstring})");
                if ( $originalModel->delete($_where) != false ) {
                    echo " --[Ok]\n";
                } else {
                    echo " --[Failed]\n";
                }
            }
            
            $Id = $ret[count($ret)-1]['Id'];
            echo "+-Next primary key={$Id}\n";

            //check and exit the program
            if ( $this->debug ) {
                break;
            }

            //check and do the interval sleep in millseconds
            if ( $interval > 0 ) {
                usleep($interval*1000);
            }
        }

        echo "+--Done, script overed!\n";
    }

    /**
     * load model from a package path
     *
     * @param   $model_path
     * @return  Mixed(IModel, Flase)
    */
    private static function loadModelFromPath($model_path)
    {
        $name    = NULL;
        $package = NULL;

        $point = strrpos($model_path, '.');
        if ( $point > 0 ) {
            $name    = substr($model_path, $point + 1);
            $package = substr($model_path, 0, $point);
        } else {
            $name = $model_path;
        }

        return Loader::model($name, $package, true);
    }

}
?>
