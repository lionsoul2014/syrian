<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/

 //------------------------------------------------------
 
class ArticleController extends C_Controller
{    
    public function _before($input, $output)
    {
        $this->model = model('article.Article');
    }
    
    public function index($input, $output)
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
    
    public function about($input, $output)
    {
        return view('article/about.html', null, true);
    }

    public function json($input, $output)
    {
        $data = array(
            'head_img'  => 'http://git.oschina.net/uploads/87/5187_lionsoul.jpg',
            'nickname'  => 'lionsoul',
            'signature' => '平凡 | 执着'
        );

        return json_view(STATUS_OK, $data);
    }
    
}
?>
