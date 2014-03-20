<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/
class ArticleController extends Controller
{	
	public function __construct()
	{
		parent::__construct();
		//$this->load->model('StreamModel');
		$this->model = Loader::model('Article', 'stream');
	}
	
	public function run()
	{
		//$this->uri->module;		//request module
		//$this->uri->page;			//request page
		
		//user logic file to handler the request
		$_logicFile = $this->uri->page . '.logic.php';
		if ( file_exists($_logicFile) )
			include $_logicFile;
		else
			redirect('/error/404', 'src_page=' . $this->uri->url);
			
		//invoke a method to handler the request
		if ( $this->uri->page == 'insert' )
			$this->index();
		$this->index();
	}
	
	public function index()
	{
		$this->uri->parse_args_get('nid/tid/pageno');
		
		//$_model = $this->loadModel('StreamModel', 'stream');
		$_ret = $this->model->getPageList($this->input->get('pageno'));
		$this->output->assign('data', $_ret);
		$this->output->setDataType($this->input->get('dataType'));
		$this->output->display('list');
	}
	
	public function insert()
	{
		//if ( $this->StreamModel->insert() )
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
			
			$this->output->assign('errno', $_errno);
		}
	}
}
?>
