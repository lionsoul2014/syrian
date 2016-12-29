<?php
/**
 * Jcseg client
 *
 * @author will<pan.kai@icloud.com>
 * @author dongyado<dongyado@gmail.com>
 *
 * @note adapted to jcseg-server new api at 2016-12-23
 */
class JcsegClient
{
    private $host;
    private $port;

    private $timeout;

    private static $EXTRACTOR_KEYWORDS_URL     = '/extractor/keywords';
    private static $EXTRACTOR_KEYPHRASE_URL    = '/extractor/keyphrase';
    private static $EXTRACTOR_KEYSENTENCE_URL  = '/extractor/sentence';
    private static $EXTRACTOR_SUMMARY_URL      = '/extractor/summary';
    private static $TOKENIZER_INSTANCE_URL     = '/tokenizer/';
    private static $SENTENCE_SPLIT_URL         = '/sentence/split';

    public function __construct($host, $port, $timeout = 20) {
        $this->host    = $host;
        $this->port    = $port;
        $this->timeout = $timeout;
    }

    /**
     * get key words
     * @param $text
     * @param $number
     * @param string $autoFilter
     * @return bool
     * @throws Exception
     */
    public  function getKeyWords($text, $number, $autoFilter = 'false') {
        $url = 'http://'.$this->host.':'. $this->port.self::$EXTRACTOR_KEYWORDS_URL."?number={$number}&autoFilter={$autoFilter}";

        import('Util');

        $postfields = array(
            'text'  => $text
        );

        $json = Util::httpPost($url, $postfields, NULL, array(CURLOPT_TIMEOUT => $this->timeout));
        if($json == false) {
            throw new Exception("Server {$this->host} Exception Error");
        }

        $json = json_decode($json, true);
        if($json['code'] != 0) {
            return false;
        }

        return $json['data']['keywords'];
    }

    /**
     * get key phrase
     * @param $text
     * @param $number
     * @param int $maxCombineLength
     * @param int $autoMinLength
     * @return bool
     * @throws Exception
     */
    public function getKeyPhrase($text, $number, $maxCombineLength = 4, $autoMinLength = 4) {
        $url  = 'http://'.$this->host.':'. $this->port.self::$EXTRACTOR_KEYPHRASE_URL."?number={$number}";
        $url .= "&maxCombineLength={$maxCombineLength}&autoMinLength={$autoMinLength}";

        import('Util');

        $postfields = array(
            'text'  => $text
        );

        $json = Util::httpPost($url, $postfields, NULL, array(CURLOPT_TIMEOUT => $this->timeout));
        if($json == false) {
            throw new Exception("Server {$this->host} Exception Error");
        }

        $json = json_decode($json, true);
        if($json['code'] != 0) {
            return false;
        }

        return $json['data']['keyphrase'];
    }

    /**
     * get key sentence
     * @param $text
     * @param $number
     * @return bool|Exception
     */
    public function getKeySentence($text, $number) {
        $url = 'http://'.$this->host.':'. $this->port.self::$EXTRACTOR_KEYSENTENCE_URL."?number={$number}";

        import('Util');

        $postfields = array(
            'text'  => $text
        );

        $json = Util::httpPost($url, $postfields, NULL, array(CURLOPT_TIMEOUT => $this->timeout));
        if($json == false) {
            return new Exception("Server {$this->host} Exception Error");
        }

        $json = json_decode($json, true);
        if($json['code'] != 0) {
            return false;
        }

        return $json['data']['sentence'];
    }

    /**
     * get sentence
     * @param $text
     * @return bool|Exception
     */
    public function getSentence($text) {
        $url = 'http://'.$this->host.':'. $this->port.self::$SENTENCE_SPLIT_URL;

        import('Util');
        $postfields = array(
            'text' => $text
        );

        $json = Util::httpPost($url, $postfields, NULL, array(CURLOPT_TIMEOUT => $this->timeout));
        if($json == false) {
            return new Exception("Server {$this->host} Exception Error");
        }

        $json = json_decode($json, true);
        if($json['code'] != 0) {
            return false;
        }

        return $json['data']['sentence'];
    }

    /**
     * get summary
     * @param $text
     * @param $length
     * @return bool
     * @throws Exception
     */
    public function getSummary($text, $length) {
        $url = 'http://'.$this->host.':'. $this->port.self::$EXTRACTOR_SUMMARY_URL."?length={$length}";

        import('Util');

        $postfields = array(
            'text'  => $text
        );

        $json = Util::httpPost($url, $postfields, NULL, array(CURLOPT_TIMEOUT => $this->timeout));
        if($json == false) {
            throw new Exception("Server {$this->host} Exception Error");
        }

        $json = json_decode($json, true);
        if($json['code'] != 0) {
            return false;
        }

        return $json['data']['summary'];
    }

    /**
     * tokenize
     * @param $text
     * @param string $tokenizer_instance
     * @param string $ret_pinyin
     * @param string $ret_pos
     * @return bool
     * @throws Exception
     */
    public function tokenize($text, $tokenizer_instance = 'extractor', $ret_pinyin = 'false', $ret_pos = 'false') {
        $url  = 'http://'.$this->host.':'. $this->port.self::$TOKENIZER_INSTANCE_URL.$tokenizer_instance;
        $url .= "?ret_pinyin={$ret_pinyin}&ret_pos={$ret_pos}";

        import('Util');

        $postfields = array(
            'text'  => $text
        );

        $json = Util::httpPost($url, $postfields, NULL, array(CURLOPT_TIMEOUT => $this->timeout));
        if($json == false) {
            throw new Exception("Server {$this->host} Exception Error");
        }

        $json = json_decode($json, true);
        if($json['code'] != 0) {
            return false;
        }

        return $json['data']['list'];
    }
}
