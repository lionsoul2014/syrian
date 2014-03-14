<?php
class ArticleModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        
        //load and create the database
        Loader::import('DbFactory', 'db');
        
        $_host = Loader::config('hosts', 'db');
        
        $this->db = DbFactory::create('Mysql', $_host['main']);
    }
    
    public function run()
    {
        //$this->db->insert();            //insert a record to the database
        echo 'model:article#run';
    }
}
?>