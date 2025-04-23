<?php
/**
 * GeoHash implementation.
 * thanks to: https://github.com/chency147/geohash.
 * 
 * Modified by Leon at 2025/03/27
*/
class GeoHash {

    // constant define
    const TOP    = 0;
    const RIGHT  = 1;
    const BOTTOM = 2;
    const LEFT   = 3;
    const EVEN   = 0;
    const ODD    = 1;

	// char pool for Base32 encode
	private $_charPool = '0123456789bcdefghjkmnpqrstuvwxyz';

	// Base32 char and its binary mapping
	private $_charPoolBin = array(
		'0' => '00000', '1' => '00001', '2' => '00010', '3' => '00011', '4' => '00100',
		'5' => '00101', '6' => '00110', '7' => '00111', '8' => '01000', '9' => '01001',
		'b' => '01010', 'c' => '01011', 'd' => '01100', 'e' => '01101', 'f' => '01110',
		'g' => '01111', 'h' => '10000', 'j' => '10001', 'k' => '10010', 'm' => '10011',
		'n' => '10100', 'p' => '10101', 'q' => '10110', 'r' => '10111', 's' => '11000',
		't' => '11001', 'u' => '11010', 'v' => '11011', 'w' => '11100', 'x' => '11101',
		'y' => '11110', 'z' => '11111',
	);

	/*
	 * base32 EVEN bit (first bit is 0) neighbors
	 *
	 * b  c  f  g  u  v  y  z
	 * 8  9  d  e  s  t  w  x
	 * 2  3  6  7  k  m  q  r
	 * 0  1  4  5  h  j  n  p
	 *
	 *
	 * base32 OOD bit(first bit is 0) neighbors
	 *
	 * p  r  x  z
	 * n  q  w  y
	 * j  m  t  v
	 * h  k  s  u
	 * 5  7  e  g
	 * 4  6  d  f
	 * 1  3  9  c
	 * 0  2  8  b
	 */

	private $_neighborChars = array(
		self::EVEN => array(
			self::TOP    => '238967debc01fg45kmstqrwxuvhjyznp',
			self::RIGHT  => '14365h7k9dcfesgujnmqp0r2twvyx8zb',
			self::BOTTOM => 'bc01fg45238967deuvhjyznpkmstqrwx',
			self::LEFT   => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy',
		),
	);

	private $_borderChars = array(
		self::EVEN => array(
			self::TOP    => 'bcfguvyz',
			self::RIGHT  => 'prxz',
			self::BOTTOM => '0145hjnp',
			self::LEFT   => '028b',
		),
	);

	public function __construct() {
		$this->_neighborChars[self::ODD] = array(
			self::TOP    => $this->_neighborChars[self::EVEN][self::RIGHT],
			self::RIGHT  => $this->_neighborChars[self::EVEN][self::TOP],
			self::BOTTOM => $this->_neighborChars[self::EVEN][self::LEFT],
			self::LEFT   => $this->_neighborChars[self::EVEN][self::BOTTOM],
		);

		$this->_borderChars[self::ODD] = array(
			self::TOP    => $this->_borderChars[self::EVEN][self::RIGHT],
			self::RIGHT  => $this->_borderChars[self::EVEN][self::TOP],
			self::BOTTOM => $this->_borderChars[self::EVEN][self::LEFT],
			self::LEFT   => $this->_borderChars[self::EVEN][self::BOTTOM],
		);
	}

	/**
	 * calc the neighbors GeoHash
	 *
	 * @param  string $hash
	 * @param  string $direction 
	 * @return string geoHash
	 */
	public function _calcNeighbor($hash, $direction) {
		$length = strlen($hash);
		if ($length == 0) {
			return '';
		}
		$lastChar  = $hash[$length - 1];
		$evenOrOdd = ($length - 1) % 2;
		$baseHash  = substr($hash, 0, -1);
		if (strpos($this->_borderChars[$evenOrOdd][$direction], $lastChar) !== false) {
			$baseHash = $this->_calcNeighbor($baseHash, $direction);
		}
		if (isset($baseHash[0])) {
			return $baseHash . $this->_neighborChars[$evenOrOdd][$direction][strpos($this->_charPool, $lastChar)];
		} else {
			return '';
		}
	}

	/**
	 * 将数值进行二进制编码
	 * 给定区间[$min, $max]，取$middle为区间中点，
     *   数值位于[$min, $middle)区间记为0，否则记为1，
     *   然后分别向左右区间逼近，循环至精度符合为止。
	 * 先产生的编码位于高位。
	 *
	 * @param float $decData 待编码数值
	 * @param float $min 区间最小值
	 * @param float $max 区间最大值
	 * @param int $precision 精度
	 * @return string 编码后二进制字段
	 */
	private function _binEncode($decData, $min, $max, $precision) {
		$result = '';
		for ($i = 0; $i < $precision; ++$i) {
			$middle = ($min + $max) / 2;
			if ($decData < $middle) {
				$result .= '0';
				$max = $middle;
			} else {
				$result .= '1';
				$min = $middle;
			}
		}
		return $result;
	}

	/**
	 * 将数值进行二进制解码，过程同_binEncode方法相反
	 *
	 * @param string $binData 二进制字符串
	 * @param float $min 区间最小值
	 * @param float $max 区间最大值
	 * @return float|int 解码后数值
	 */
	private function _binDecode($binData, $min, $max) {
		$middle = ($min + $max) / 2;
		$binLength = strlen($binData);
		for ($i = 0; $i < $binLength; ++$i) {
			if ($binData[$i] == '0') {
				$max = $middle;
				$middle = ($min + $middle) / 2;
			} else {
				$min = $middle;
				$middle = ($middle + $max) / 2;
			}
		}
		return $middle;
	}

	/**
	 * 将两个二进制字符串错位合并，位数较小的字符串以「0」填充
	 * 注意：数组下标从「0」开始，0是偶数
	 *
	 * @param string $binFirst 占据偶数位的二进制字符串
	 * @param string $binSecond 占据奇数位的二进制字符串
	 * @return string 合并后字符串
	 */
	private function _binCombine($binFirst, $binSecond) {
		$result = '';
		$i = 0;
		while (isset($binFirst[$i]) || isset($binSecond[$i])) {
			$result .= (isset($binFirst[$i]) ? $binFirst[$i] : '') 
                . (isset($binSecond[$i]) ? $binSecond[$i] : '');
			++$i;
		}
		return $result;
	}

	/**
	 * 二进制字符串展开，同binCombine方法相反
	 *
	 * @param string $binData 二进制字符串
	 * @return array array[0]表示经度，array[1]表示纬度
	 */
	private function _binExplode($binData) {
		$result = array(
			0 => '',
			1 => '',
		);
		$binLength = strlen($binData);
		for ($i = 0; $i < $binLength; ++$i) {
			$result[$i % 2] .= $binData[$i];
		}
		return $result;
	}

	/**
	 * Base32编码
	 *
	 * @param string $binData 待编码二进制字符串
	 * @return string 编码后字符串
	 */
	private function _base32Encode($binData) {
		$binLength = strlen($binData);
		$result = '';
		if ($binLength == 0) {
			return $result;
		}
		$fix = 5 - ($binLength % 5);
		if ($fix < 5) {
			$binData .= str_repeat('0', $fix);
			$binLength += $fix;
		}
		for ($i = 0; $i < $binLength; $i += 5) {
			$tmp = substr($binData, $i, 5);
			$result .= $this->_charPool[bindec($tmp)];
		}
		return $result;
	}

	/**
	 * Base32解码
	 *
	 * @param string $base32Data 待解码Base32字符串
	 * @return string 解码后二进制字符串
	 */
	private function _base32Decode($base32Data) {
		$len = strlen($base32Data);
		$result = '';
		for ($i = 0; $i < $len; ++$i) {
			$result .= $this->_charPoolBin[$base32Data[$i]];
		}
		return $result;
	}

	/**
	 * 计算至少所需二进制位数
	 *
	 * @param float $data 经度或纬度
	 * @param float $basePrecision 当二进制位数为1的时候，数据能表示的范围长度的一半
	 * @return int
	 */
	private function _calcPrecision($data, $basePrecision) {
		$dotIndex = strpos($data, '.');
		$result = 1;
		if ($dotIndex === false) {
			return $result;
		}
		$needPrecision = pow(10, -(strlen($data) - $dotIndex - 1)) / 2;
		while ($basePrecision > $needPrecision) {
			++$result;
			$basePrecision /= 2;
		}
		return $result;
	}

	/**
	 * 计算二进制转换后，长度所对应的误差
	 *
	 * @param int $length 二进制字符串长度
	 * @param float $min 区间最小值
	 * @param float $max 区间最大值
	 * @return float|int 误差
	 */
	private function _calcError($length, $min, $max) {
		$error = ($max - $min) / 2;
		while ($length > 0) {
			$error /= 2;
			--$length;
		}
		return $error;
	}

	/**
	 * 计算解码后的精确的小数点后位数
	 *
	 * @param int $length 二进制字符串长度
	 * @param float $min 区间最小值
	 * @param float $max 区间最大值
	 * @return int 小数点后位数
	 */
	private function _calcDecodePrecision($length, $min, $max) {
		$error = $this->_calcError($length, $min, $max);
		$tmp = 0.1;
		$i = 0;
		while ($tmp > $error) {
			$tmp /= 10;
			++$i;
		}
		return $i;
	}

	/**
	 * 二进制位数修正，使经度和纬度的二进制位数之和为5的倍数
	 *
	 * @param int $longBits 经度位数
	 * @param int $latBits 纬度位数
	 */
	private function _bitsFix(&$longBits, &$latBits) {
		$maxBits = max($longBits, $latBits);
		$longBits = $latBits = $maxBits;
		$i = 0;
		while (($longBits + $latBits) % 5 != 0) {
			if ($i % 2 == 0) {
				++$longBits;
			} else {
				++$latBits;
			}
			++$i;
		}
	}

	/**
	 * 经纬度编码为GeoHash
	 *
	 * @param float $long 经度
	 * @param float $lat 纬度
	 * @return string 编码后的GeoHash
	 */
	public function encode($long, $lat) {
		// 计算经纬度转换后所需的二进制长度
		$longBit = $this->_calcPrecision($long, 90);
		$latBit = $this->_calcPrecision($lat, 45);
		// 修正上边的长度，使之和为5的倍数
		$this->_bitsFix($longBit, $latBit);
		// 对经纬度进行二进制编码
		$longBin = $this->_binEncode($long, -180, 180, $longBit);
		$latBin = $this->_binEncode($lat, -90, 90, $latBit);
		// 合并两个二进制编码
		$combinedBin = $this->_binCombine($longBin, $latBin);
		// Base32编码
		return $this->_base32Encode($combinedBin);
	}

	/**
	 * GeoHash解码为经纬度
	 *
	 * @param string $hash geoHash
	 * @return array array[0]表示经度，array[1]表示纬度
	 */
	public function decode($hash) {
		// Base32解码
		$combinedBin = $this->_base32Decode($hash);
		// 拆分合并后的二进制编码
		$result = $this->_binExplode($combinedBin);
		$longBin = $result[0];
		$latBin = $result[1];
		// 二进制解码
		$long = $this->_binDecode($longBin, -180, 180);
		$lat = $this->_binDecode($latBin, -90, 90);
		// 根据精度修正经纬度
		$long = round($long, $this->_calcDecodePrecision(strlen($longBin), -180, 180));
		$lat = round($lat, $this->_calcDecodePrecision(strlen($latBin), -90, 90));
		return array($long, $lat);
	}

	/**
	 * 获取周边8个矩阵的地理编码
	 *
	 * @param string $hash 已知GeoHash
	 * @return array
	 */
	public function neighbors($hash) {
		$hashNorth = $this->_calcNeighbor($hash, self::TOP);
		$hashEast  = $this->_calcNeighbor($hash, self::RIGHT);
		$hashSouth = $this->_calcNeighbor($hash, self::BOTTOM);
		$hashWest  = $this->_calcNeighbor($hash, self::LEFT);

		$hashNorthEast = $this->_calcNeighbor($hashNorth, self::RIGHT);
		$hashSouthEast = $this->_calcNeighbor($hashSouth, self::RIGHT);
		$hashSouthWest = $this->_calcNeighbor($hashSouth, self::LEFT);
		$hashNorthWest = $this->_calcNeighbor($hashNorth, self::LEFT);
		return array(
			'North'     => &$hashNorth,
			'East'      => &$hashEast,
			'South'     => &$hashSouth,
			'West'      => &$hashWest,
			'NorthEast' => &$hashNorthEast,
			'SouthEast' => &$hashSouthEast,
			'SouthWest' => &$hashSouthWest,
			'NorthWest' => &$hashNorthWest,
		);
	}

}
