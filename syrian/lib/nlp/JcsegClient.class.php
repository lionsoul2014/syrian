<?php
/**
 * http client for Jcseg NLP framework
 *
 * @since   2.0.1
 * @author  chenxin<chenxin619315@gmail.com>
*/

//-----------------------------------

class JcsegClient
{
    /**
     * server exchange protocol
    */
    private $protocol = 'http';

    /**
     * jcseg server host
     * an ip address, domain name or whatever
    */
    private $host = 'localhost';

    /**
     * the server port and default to 1990
    */
    private $port = 1990;

    /**
     * default request timeout in seconds
    */
    private $timeout = 15;

    /**
     * construct method to initialize the instance
     *
     * @param   $conf - whatever you need
     * array(
     *  protocol => http,
     *  host => localhost,
     *  port => 1990
     * )
    */
    public function __construct($conf)
    {
        if ( isset($conf['protocol']) ) $this->protocol = $conf['protocol'];
        if ( isset($conf['timeout'])  ) $this->timeout  = $conf['timeout'];
        if ( isset($conf['host']) ) $this->host = $conf['host'];
        if ( isset($conf['port']) ) $this->port = $conf['port'];
    }

    /**
     * do the final http request and parse the server response
     * And this is the core part of all the orther direct function interface
     *
     * @param   $uri with the query string maybe
     * @param   $param post arguments string or Array
     * @return  Mixed Array for succeed or false for failed
    */
    protected function _do_request($uri, $param=null)
    {
        $curl = curl_init();
        if( $this->protocol == 'https' ) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $postfields = NULL;
        if ( is_string($param) ) $postfields = $param;
        else if ( $param != null ) {
            $args = array();
            foreach ( $param as $key => $val) {
                $args[] = "{$key}=".urlencode($val);
            }

            $postfields = implode('&', $args);
            unset($args);
        }

        $url = "{$this->protocol}://{$this->host}:{$this->port}{$uri}";
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
        curl_setopt($curl, CURLOPT_HTTPHEADER, 
            array('User-Agent: Jcseg client 2.0.1/@lang:php/@date:2016-12-30')
        );
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        $ret  = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        /*
         * http status 200 and the response string is a valid json string
         * and the json->code is 0 (means for succeed).
         * check http://git.oschina.net/lionsoul/jcseg 
         *  or https://github.com/lionsoul2014/jcseg
         * for more documentation
        */
        if ( intval($info['http_code']) == 200 
            && ($json = json_decode($ret)) != null && $json->code === 0 ) {
            return $json->data;
        }

        return false;
    }


    //---------------------interface-------------------

    /**
     * do the tokenize for the specifield string or maybe a string Array
     * @Note: string Array not implemented
     *
     * @param   $text the text to tokenize
     * @param   $instance the tokenizer instance name define in the jcseg-server.properties
     * @return  Mixed json array for success or false for failed
    */
    public function tokenize($text, $inst_name='master')
    {
        $json = $this->_do_request(
            "/tokenizer/{$inst_name}", 'text=' . urlencode($text)
        );
        if ( $json == false ) {
            return false;
        }

        return $json->list;
    }

    /**
     * extract the summary of the specifield string
     *
     * @param   $text
     * @return  Mixed string or false for failed
    */
    public function summary($text, $length=64)
    {
        $json = $this->_do_request(
            "/extractor/summary?length=${length}", 'text=' . urlencode($text)
        );
        if ( $json == false ) {
            return false;
        }

        return $json->summary;
    }

    /**
     * key sentence extract for the specifield string
     *
     * @param   $text
     * @param   $number number of sentence to extract
     * @return  Mixed json Array or false for failed
    */
    public function keySentence($text, $number=5)
    {
        $json = $this->_do_request(
            "/extractor/sentence?number=${number}", 'text=' . urlencode($text)
        );
        if ( $json == false ) {
            return false;
        }

        return $json->sentence;;
    }

    /**
     * keywords extract for the specifield string
     *
     * @param   $text
     * @param   $number number of sentence to extract
     * @return  Mixed json Array or false for failed
    */
    public function keywords($text, $number=10, $autoFilter=false)
    {
        $fstr = $autoFilter ? 'true' : 'false';
        $json = $this->_do_request(
            "/extractor/keywords?number=${number}&autoFilter={$fstr}",
            'text=' . urlencode($text)
        );
        if ( $json == false ) {
            return false;
        }

        return $json->keywords;;
    }

}
?>
