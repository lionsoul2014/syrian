<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/
class ArticleController extends Controller
{	
	public function __construc( )
	{
		parent::__construct();
		
		//Better not do any initialize work here
	}
	
	public function run()
	{
		//Load article model
		$this->model = Loader::model('Article', 'article');
		
		$this->view  = $this->getHtmlView();
		$this->view->assign('title', '开源软件 - 平凡 | 执著');
		
		//invoke a method to handler the request
		if ( $this->uri->page == 'about' ) $this->about();
		else $this->index();
	}
	
	public function index()
	{
		$this->view->assoc('data', $this->model->getList(1));
		
		//get the executed html content
		$_html = $this->view->getHtml('list.html');
		
		$this->output->compress(9);		//set the compress level
		$this->output->display($_html);
	}
	
	public function about()
	{
		$this->view->assoc('about', $this->model->getItem());
		
		//get the executed html content
		$_html = $this->view->getHtml('about.html');
		
		$this->output->compress(9);		//set the compress level
		$this->output->display($_html);
	}
	
	public function insert()
	{
		$this->input->get('id', array(OP_INT, OP_SIZE(2,10), OP_SANTILIZE_SCRIPT));
		if ( $this->input->post('_act') != FALSE )
		{
			$_model = array(
				'name'		=> array(OP_STRING, OP_LIMIT(6, 120), OP_SANITIZE_HTML),
				'age'		=> array(OP_INT, OP_SIZE(10, 120), OP_SANITIZE_INT),
				'brief'		=> array(OP_STRING, NULL, OP_SANITIZE_HTML)
			);
			$_errmsg = array(
				'name'		=> array('姓名尚未填写', '长度必须为6-120个字符'),
				'sex'		=> array('年龄必须为整数', '年龄大小必须为10-120'),
				'brief'		=> array(NULL, $this->lang->InvalidBriefContent)
			);
			
			//$name = $this->input->post('name');
			//$body = $this->input->post('sex');
			
			if ( ($_ret = $this->input->postModel($_model, $_erridx) ) )
			{
				//$_errno = $this->StreamModel->insert($name, $body);
				$_errno = $this->model->update($_ret,
					'id='.$this->get('id').' and user_id='.$this->session('user_id'));
			}
			else
			{
				$_errno = $_errmsg[$_erridx[0]][$_erridx[1]];
			}
			
			$this->view->assign('errno', $_errno);
		}
	}
}
?>
