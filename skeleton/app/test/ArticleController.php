<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/

class ArticleController extends C_Controller
{    
    public function __before($input, $output, $uri)
    {
        $this->model = model('article.Article');
    }
    
    public function index($input)
    {
        $pageno = $input->getInt('pageno', 1);

        return view(
            'article/list.html', 
            array(
                'pageno' => $pageno,
                'data'   => $this->model->getSoftList($pageno)
            ), true
        );
    }
    
    public function about($input)
    {
        return view('article/about.html', null, true);
    }
    
}