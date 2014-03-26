<?php
	$this->view->assoc('about', $this->model->getItem());
	
	//get the executed html content
	$_html = $this->view->getHtml('about.html');
	
	$this->output->compress(9);		//set the compress level
	$this->output->display($_html);
?>
