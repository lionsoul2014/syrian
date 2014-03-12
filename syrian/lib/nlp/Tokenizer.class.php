<?php
/**
 * A simple chinese tokenizer written by php.
 * 	implemented the forward maximum matching algorithm.
 * 			Only works for UTF-8 charset.
 * 	
 * If you could install an extension yourself, try
 * 		http://code.google.com/p/robbe chinese tokenizer extension.
 *
 * function:
 * 1. forward maximum matching.
 * 2. chinese puncutation clear.
 * 3. stopwords clear.
 * 4. uppercase/lowercase convert.
 * 5. full-width/half-width convert.	(-undone)
 *
 * @author chenxin <chenxin619315@gmail.com>
*/

class Tokenizer
{
	private $_dic = NULL;	//dictionary file resource.
	private $_clrSTW;		//Wether to clear the stopwords.
	private $_maxLen;		//Maximum matching length.
	
	//do not change the following variables.
	private $__opacity	= 10368963;
	private $__length	= 3456321;
	private $__conflict = 3;
	
	public function __construct( $_dfile, $_maxLen = 3, $_clr_stw = true )
	{
		//tokenzier config
		$this->_maxLen = $_maxLen;
		$this->_clrSTW = $_clr_stw;
		
		$this->_dic = fopen($_dfile, 'rb');
		if ( $this->_dic == FALSE )
			die("Error: Fail to open the dictionary file.");
			
		//lock the dictionary file.
		flock( $this->_dic, LOCK_SH );
	}
	
	/**
	 * tokenize the specified string.
	 *
	 * @param	$_text	text to tokenize
	 * @return	Array
	*/
	public function split( $_text )
	{
		$idx = 0;						//current offset.
		$_length = strlen($_text);		//length of the text.
		$_ret = array();				//tokenize result.
		$_word = NULL;
		$_tmp = NULL;
		
		while ( $idx < $_length )
		{
			$_word = '';			//current tokenzier result.
			
			/*
			 * here get the bytes of the next word.
			 * 	it could be any kinds of language, and we only care about
			 * 		the english and the CJK words.
			 * this will avoid the errors cause by unknow languages.
			*/
			$_bytes = self::getUtf8Bytes( $_text[$idx] );
			//echo $_bytes, '<br />';
			if ( ! ( $_bytes == 1 || $_bytes == 3 ) )
			{
				$idx += $_bytes;
				continue;
			}
			
			//basic english segmentation.
			if ( $_bytes == 1 )		
			{
				$u = ord($_text[$idx]);
				
				//basic numeric.
				if ( self::isNumeric( $u ) )
				{
					$_word .= $_text[$idx++];
					for ( ; $idx < $_length; ) {
						$u = ord($_text[$idx]);
						if ( ! self::isNumeric( $u ) ) break;
						$_word .= $_text[$idx++];
					}
				}
				//basic english chars.
				else if ( self::isLetter( $u ) )
				{
					//convert uppercase to lowercase.
					if ( $u >= 97 )	$_word .= $_text[$idx];
					else $_word .= chr($u + 32);
					$idx++;
					
					for ( ; $idx < $_length; ) {
						$u = ord($_text[$idx]);
						if ( ! self::isLetter( $u ) ) break;
						//convert uppercase to lowercase.
						if ( $u >= 97 )	$_word .= $_text[$idx];
						else $_word .= chr($u + 32);
						//$_word .= $_text[$idx++];
						$idx++;
					}
				}
				//punctuation/unrecognized chars. -ignore
				else {
					$idx++;
					continue;
				}
			}
			//chinese segmentation.
			else
			{
				//chinese punctuation - ignore
				$_word = substr($_text, $idx, 3);
				$idx += 3;
				if ( isset( $this->_CJKPunctuation[$_word] ) ) continue;
				
				//chinese words maxinum matching. (maximum length 3)
				$_tmp = $_word;
				for ( $i = 1, $j = $idx;
					 $i < $this->_maxLen && $j < $_length; $i++ )
				{
					$_bytes = self::getUtf8Bytes($_text[$j]);
					if ( $_bytes != 3 ) break;
					$_tmp .= ($_text[$j++] . $_text[$j++] . $_text[$j++]);
					if ( $this->isDicExists( $_tmp, strlen($_tmp) ) ) {
						$_word = $_tmp;
					}
				}
				
				//update the current offset.
				$idx += strlen($_word) - 3;
			}
			
			/*
			 * check the stopwords dictionary.
			 * 		clear the stopwords if 
			*/
			if ( $this->_clrSTW
					&& isset($this->_stopwords[$_word]) ) continue;
			//echo $_word, '<br />';
			$_ret[] = $_word;
			
		}		//end while
		
		return $_ret;
	}
	
	/**
	 * check the dictionary wether the specified
	 * 		word is a valid chinese word in the dictionary.
	 *
	 * @param	$_word
	 * @param	$_size	- bytes to check.
	 * @return	bool
	*/
	private function isDicExists( $_word, $_size )
	{
		//count the hash
		$_offset = self::bkdrHash($_word, $this->__length);
		//echo $_word . ':' . $_size . '=>' . $hash . "\n";
		for ( $i = 0; $i < $this->__conflict; $i++ )
		{
			fseek($this->_dic, $_offset, SEEK_SET);
			if ( fread($this->_dic, $_size) == $_word )
				return true;
			else
				$_offset += $this->__length;
		}
		
		return false;
	}
	
	/*
	 * check the specified char is an chinese puncutation.
	 * 		Only work for UTF-8 charset.
	 *
	 * @param	$_str
	 * @return	bool
	*/
	private $_CJKPunctuation = array('！'=>1, '＂'=>1, '＃'=>1, '＄'=>1, '％'=>1, '＆'=>1, '＇'=>1, '（'=>1,
			'）'=>1, '＊'=>1, '＋'=>1, '，'=>1, '－'=>1, '．'=>1, '／'=>1, '：'=>1, '；'=>1, '＜'=>1, '＝'=>1,
			'＞'=>1, '？'=>1, '＠'=>1, '［'=>1, '＼'=>1, '］'=>1, '＾'=>1, '＿'=>1, '｀'=>1, '｛'=>1, '｜'=>1,
			'｝'=>1, '～'=>1, '｟'=>1, '｠'=>1, '｡'=>1, '｢'=>1, '｣、'=>1, '。'=>1, '〃'=>1, '〄'=>1, '々'=>1,
			'〆'=>1, '〇'=>1, '〈'=>1, '〉'=>1, '《'=>1, '》'=>1, '「'=>1, '」'=>1, '『'=>1, '』'=>1, '【'=>1,
			'】'=>1, '〒'=>1, '〓'=>1, '〔'=>1, '〕'=>1, '〖'=>1, '〗'=>1, '〘'=>1, '〙'=>1, '〚'=>1, '〛'=>1,
			'〜'=>1, '〝'=>1, '〞'=>1, '〟'=>1, '￥'=>1, '…'=>1, '×'=>1, '—'=>1, '“'=>1, '”'=>1, '‘'=>1,
			'|'=>1, '、'=>1);
	
	/**
	 * check the specified word is an stopwords.
	 *
	 * @param	$_str
	 * @return	bool
	*/
	private $_stopwords = array('的'=>1, '吗'=>1, '不'=>1,'我'=>1, '们'=>1, '起'=>1, '就'=>1, '最'=>1,
			'在'=>1, '人'=>1, '有'=>1, '是'=>1, '为'=>1, '以'=>1, '于'=>1, '上'=>1, '他'=>1, '而'=>1,
			'后'=>1, '之'=>1, '来'=>1, '由'=>1, '及'=>1, '了'=>1, '下'=>1, '可'=>1, '到'=>1, '这'=>1,
			'与'=>1, '也'=>1, '因'=>1, '此'=>1, '但'=>1, '并'=>1, '个'=>1, '其'=>1, '已'=>1, '无'=>1,
			'小'=>1, '今'=>1, '去'=>1, '再'=>1, '好'=>1, '只'=>1, '又'=>1, '或'=>1, '很'=>1, '亦'=>1,
			'某'=>1, '把'=>1, '那'=>1, '你'=>1, '乃'=>1, '它'=>1, '吧'=>1, '被'=>1, '比'=>1, '别'=>1,
			'趁'=>1, '当'=>1, '从'=>1, '到'=>1, '得'=>1, '打'=>1, '凡'=>1, '儿'=>1, '尔'=>1, '该'=>1,
			'各'=>1, '给'=>1, '跟'=>1, '和'=>1, '何'=>1, '还'=>1, '即'=>1, '几'=>1, '既'=>1, '看'=>1,
			'据'=>1, '距'=>1, '靠'=>1, '啦'=>1, '了'=>1, '另'=>1, '么'=>1, '每'=>1, '们'=>1, '嘛'=>1,
			'拿'=>1, '哪'=>1, '那'=>1, '您'=>1, '凭'=>1, '且'=>1, '却'=>1, '让'=>1, '仍'=>1, '啥'=>1,
			'如'=>1, '若'=>1, '使'=>1, '谁'=>1, '虽'=>1, '随'=>1, '同'=>1, '所'=>1, '她'=>1, '哇'=>1,
			'嗡'=>1, '往'=>1, '哪'=>1, '些'=>1, '向'=>1, '沿'=>1, '哟'=>1, '用'=>1, '于'=>1, '咱'=>1,
			'则'=>1, '怎'=>1, '曾'=>1, '至'=>1, '致'=>1, '着'=>1, '诸'=>1, '自'=>1, '啊'=>1);
	
	/**
	 * BKDR simple hash algorithm.
	 *
	 * @param	$_str
	 * @return	Integer	hash of the $_str.
	*/
	private function bkdrHash( $_str, $_size )
	{
		$_hash = 0;
		$len = strlen($_str);
	
		for ( $i = 0; $i < $len; $i++ ) {
			$_hash = ( int ) ( $_hash * 1331 + ( ord($_str[$i]) % 127 ) );
		}
		
		if ( $_hash < 0 ) 			$_hash *= -1;
		if ( $_hash >= $_size ) 	$_hash = ( int ) $_hash % $_size; 
		
		return ( $_hash & 0x7FFFFFFF );
	}
	
	/**
	 * check the specified char is an english numeric.
	 * 		half-width only.
	 *
	 * @param	$u	Unicode/ASCII code number.
	 * @return	bool
	*/
	private static function isLetter( $u )
	{
		return ( ($u >=  65 && $u <= 90)			//uppercase
				|| ($u >= 97 && $u <= 122) );		//lowercase
	}
	
	/**
	 * get the bytes of a utf-8 char.
	 * 		between 1 - 6.
	 *
	 * @param	$_char
	 * @return	bool 
	 */
	private static function getUtf8Bytes( $_char ) 
	{	
		$t = 0;
		$_val = ord($_char);
	
		//one byte ascii char.
		if ( ( $_val & 0x80 ) == 0 ) return 1;
	
		for ( ; ( $_val & 0x80 ) != 0; $_val <<= 1 ) 
			$t++;
	
		return $t;
	}
	
	/**
	 * check the specified char is an english numeric.
	 * 		half-width only.
	 *
	 * @param	$u	Unicode/ASCII code number.
	 * @return	bool
	*/
	private static function isNumeric( $u ) 
	{
		return ($u >= 48 && $u <= 57);
	}
	
	
	public function __destruct()
	{
		if ( $this->_dic != NULL ) {
			flock($this->_dic, LOCK_UN);
			fclose($this->_dic);
		}
	}
}
?>