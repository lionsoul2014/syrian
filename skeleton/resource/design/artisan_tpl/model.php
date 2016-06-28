<?php

echo <<<COMMENT
<?php
/**
 * {$artisan['desc']}
 *
 * @author {$artisan['author']}<{$artisan['email']}>
 * @date   {$artisan['date']}
 */
COMMENT;

if ( $artisan['model'] == 'sharding' ) {
$tplStr = <<<TPL

Loader::import('RouterShardingModel', NULL, false);

class {$artisan['name']}Model extends RouterShardingModel
{
	public function __construct()
	{
		parent::__construct();

		//router base algorithm must need the router
        {this}->router = 'router_name';
		// {this}->guidKey = 'Id';

        /*
         * once defined, the order of the sharding
         * (sub-model) can not be changed
        */
		{this}->shardings = array(
            // array('sharding01', 'section'),
        );
	}

}

TPL;
} else if ( $artisan['model'] == 'elasticSearch' ) {
$tplStr = <<<TPL

Loader::import('ElasticSearchModel', NULL, false);

class {$artisan['name']}Model extends ElasticSearchModel
{
	public function __construct()
	{
		parent::__construct();

		/*
         * set the basic primary key
        */
        // {this}->primary_key = 'primary_key';

        {this}->type   = 'type_name';
        {this}->fields = array(
	        // 'keywords'  => array(
	        //     'type'  => 'string',
	        //     'store' => 'no',
	        //     'index' => 'not_analyzed'
	        // ),
        );

        //initialize the model from the db configuration
        //@Note=> invoke this after the type setting
        {this}->_InitFromConfigByKey('config_key_name');
	}

}

TPL;
} else {
$tplStr = <<<TPL

class {$artisan['name']}Model extends C_Model
{
	public function __construct()
	{
		parent::__construct();

		{this}->db    = C_Model::getDatabase('Mysql', "{$artisan['db']}");
		{this}->table = "{$artisan['prefix']}{$artisan['table']}";
		{this}->primary_key = "{$artisan['pk']}";
	}

}

TPL;
}

echo $tplStr;
