<?php
class ArticleModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function run()
    {
        echo 'model:article#run';
    }
}
?>