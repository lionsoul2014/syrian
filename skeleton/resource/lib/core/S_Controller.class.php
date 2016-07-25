<?php
/**
 * Script common Controller class
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

 //-----------------------------------------------------
 
class S_Controller extends Controller
{
    public function run($input, $output, $uri)
    {
        if ( strlen($uri->page) < 1 ) {
            throw new Exception("S_Controller#run: no method define\n");
        }

        if ( $this->conf->script['debug'] == true ) {
            _G(SR_FLUSH_MODE, true);
        }

        if ( $this->conf->script['compress'] > 0 ) {
            $output->compress($this->conf->script['compress']);
        }

        $output->setHeader('Content-Type', 'application/javascript');

        /*
         * build the cache object
         * then check and return the cache if it is available
         * @Note: directly ignore the cache if it is flush mode
        */
        $cache   = build_cache('ScriptMerge');
        $baseKey = $uri->package==null ? $uri->module : "{$uri->package}/{$uri->module}";
        $cache->baseKey($baseKey)->fname($uri->page[0]);
        if ( _G(SR_FLUSH_MODE) == false ) {
            if ( ($CC = $cache->get(-1)) != false ) {
                return $CC;
            }
        }

        $scripts = parent::run($input, $output, $uri);
        if ( ! is_array($scripts) ) {
            return null;
        }

        import('html.ScriptMerge');
        import('html.JsMinifier');

        $MG = new ScriptMerge(SR_STATICPATH.'js/', true);
        $CC = $MG->appendArray($scripts)->merge()->getContent();

        //minify the whole script
        $CC = JsMinifier::minify($CC, array('flaggedComments' => false));
        $cache->set($CC);

        return $CC;
    }

}
?>
