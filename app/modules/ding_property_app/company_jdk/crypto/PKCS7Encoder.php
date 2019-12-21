<?php
namespace  app\modules\ding_property_app\company_jdk\crypto;

class PKCS7Encoder
{
	public static $block_size = 32;

	function encode($text)
	{
		$block_size = self::$block_size;
		$text_length = strlen($text);
		$amount_to_pad = self::$block_size - ($text_length % self::$block_size);
		if ($amount_to_pad == 0) {
			$amount_to_pad = self::$block_size;
		}
		$pad_chr = chr($amount_to_pad);
		$tmp = "";
		for ($index = 0; $index < $amount_to_pad; $index++) {
			$tmp .= $pad_chr;
		}
		return $text . $tmp;
	}

	function decode($text)
	{
		$pad = ord(substr($text, -1));
		if ($pad < 1 || $pad > self::$block_size) {
			$pad = 0;
		}
		return substr($text, 0, (strlen($text) - $pad));
	}

}
?>