<?php
/**
 * ArticleController form stream module
 * 
 * @author chenxin <chenxin619315@gmail.com>
*/

 //------------------------------------------------------

import('core.C_Controller', false);
 
class ArticleController extends C_Controller
{    
    public function __before($input, $output)
    {
        $this->model = model('article.Article');
    }
    
    public function _index($input, $output)
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
    
    public function _about($input, $output)
    {
        return view('article/about.html', null, true);
    }

    public function _json($input, $output)
    {
        return array(
            'head_img'  => 'http://git.oschina.net/uploads/87/5187_lionsoul.jpg',
            'nickname'  => 'lionsoul',
            'signature' => '平凡 | 执着'
        );
    }

    public function _profile()
    {
        $data = array(
            'head_img'  => 'http://git.oschina.net/uploads/87/5187_lionsoul.jpg',
            'nickname'  => 'lionsoul',
            'signature' => '平凡 | 执着'
        );

        //return json_view(STATUS_OK, $data);
        return json_define_view(STATUS_OK, json_encode($data));
    }
    
}
?>
