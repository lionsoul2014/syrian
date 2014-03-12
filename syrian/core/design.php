<?php
#$this->config;
#$this->input;
#$this->view;
#$this->blog = load_model('blog');

class Controller
{
	private $_args = array();
	
	public function __construct()
	{
		$this->input = 'abc';
	}
	
	public function response()
	{
		//1.此处需要得到请求的信息。URI->module, URI->page
		$this->uri;
		
		//2.需要得到操作数据库的信息。（基类）
		$this->getdatabase();
		
		//3.需要得到模板信息。
		$this->getView();
		
		//4.需要得到输入信息。
		$this->getInput();
		
		//5.需要获取配置信息。
		$this->getConfig();
		
		//6.需要载入操作模型。(公共函数部分, Common.php)
		load_model();
		
	}
}

//1.URI.class.php
?>