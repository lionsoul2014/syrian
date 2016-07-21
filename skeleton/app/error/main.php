<?php
/**
 * ErrorController
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

class ErrorController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function _404()
    {
        return '404 that\' an error.';
    }
}
?>
