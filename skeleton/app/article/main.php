<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/

 //------------------------------------------------------
 
class ArticleController extends STDController
{    
    public function __construc( )
    {
        parent::__construct();

        $this->vc_time     = -1;
    }
    
    public function run()
    {
        parent::run();
        $this->view->assoc('site', $this->sysconf);

        //Load article model
        $this->model = Loader::model('Article', 'article');
        
        //invoke a method to handler the request
        if ( strncmp($this->uri->page, 'lionsoul', 8) == 0 ) $this->about();
        else $this->index();
    }
    
    public function index()
    {
        $pageno = $this->input->getInt('paegno');
        if ( $pageno == false ) $pageno = 1;

        $this->view->assign('data', $this->model->getSoftList($pageno));
        
        //get the executed html content
        $ret     = $this->view->getContent('article/list.html');
        //$this->output->compress(9);
        $this->output->display($ret);
    }
    
    public function about()
    {
        //get the executed html content
        $ret     = $this->view->getContent('article/about.html');
        
        //$this->output->compress(9);        //set the compress level
        $this->output->display($ret);
    }
    
    public function insert()
    {
        if ( $this->input->post('_act') != FALSE )
        {
            $_model = array(
                'name'        => array(OP_STRING, OP_LIMIT(6, 120), OP_SANITIZE_HTML),
                'age'        => array(OP_INT, OP_SIZE(10, 120), OP_SANITIZE_INT),
                'brief'        => array(OP_STRING, NULL, OP_SANITIZE_HTML)
            );
            $_errmsg = array(
                'name'        => array('姓名尚未填写', '长度必须为6-120个字符'),
                'sex'        => array('年龄必须为整数', '年龄大小必须为10-120'),
                'brief'        => array(NULL, $this->lang->InvalidBriefContent)
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
