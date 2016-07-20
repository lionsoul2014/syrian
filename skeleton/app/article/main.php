<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/

 //------------------------------------------------------
 
class ArticleController extends C_Controller
{    
    public function __construc( )
    {
        parent::__construct();

        $this->vc_time = -1;
    }
    
    public function run($input, $output)
    {
        parent::run($input, $output);
        
        //Load article model
        $this->model = model('article.Article');
        
        //invoke a method to handler the request
        if ( strncmp($this->uri->page, 'lionsoul', 8) == 0 ) return $this->about($input, $output);
        else return $this->index($input, $output);
    }
    
    public function index($input, $output)
    {
        $pageno = $input->getInt('pageno', 1);

        return html_view('article/list.html', array(
            'pageno' => $pageno,
            'data'   => $this->model->getSoftList($pageno)
        ), true);
    }
    
    public function about($input, $output)
    {
        return html_view('article/about.html', null, true);
    }
    
}
?>
