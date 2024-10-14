<?php

/**
 * Bas uses checksum signature to ensure that API requests and responses shared between your 
 * application and Bas over network have not been tampered with. We use SHA256 hashing and 
 * AES128 encryption algorithm to ensure the safety of transaction data.
 *
 * @author     Kamal Hassan - Abdullah Al-Ansi
 * @version    0.0.1
 * @link       https://.basgate.com/docs/
 */



class BasChecksum
{
	public static function encryptString($plainText, $key, $iv)
	{
		$cipher = "aes-256-cbc";
		$key = substr($key, 0, 32);
		$encrypted = openssl_encrypt($plainText, $cipher, $key, 0, $iv);
		return $encrypted;
	}

	public static function decryptString($cipherText, $key, $iv)
	{
		$cipher = "aes-256-cbc";
		$key = substr($key, 0, 32);
		$decrypted = openssl_decrypt($cipherText, $cipher, $key, 0, $iv);
		return $decrypted;
	}

	public static function generateSignature($input, $key)
	{
		try {
			self::validateGenerateCheckSumInput($key);
			$stringBuilder = $input . '|';
			$randomString = self::generateRandomString(4);
			$stringBuilder .= $randomString;
			$hash = self::getHashedString($stringBuilder);
			$hashRandom = $hash . $randomString;
			// echo "======generateSignature hashRandom: $hashRandom\n";
			$encrypt = self::encrypt($hashRandom, $key);
			echo "======generateSignature encrypt: $encrypt\n";
			echo "======generateSignature input: $input\n";
			return $encrypt;
		} catch (Exception $ex) {
			self::showException($ex);
			return null;
		}
	}

	public static function verifySignature($input, $key, $checkSum)
	{
		try {
			self::validateVerifyCheckSumInput($checkSum, $key);
			$str1 = self::decrypt($checkSum, $key);
			if ($str1 === null || strlen($str1) < 4) {
				return false;
			}
			$str2 = substr($str1, -4);
			$stringBuilder = $input . '|' . $str2;
			$source = self::getHashedString($stringBuilder);
			return $str1 === $source . $str2;
		} catch (Exception $ex) {
			self::showException($ex);
			return false;
		}
	}

	public static function encrypt($input, $key)
	{
		$key0 = hash('sha256', $key, true);
		try {
			$iv = pack('C*', 64, 64, 64, 64, 38, 38, 38, 38, 35, 35, 35, 35, 36, 36, 36, 36);
			$encrypted = openssl_encrypt($input, 'aes-256-cbc', $key0, 0, $iv);
			return $encrypted;
		} catch (Exception $ex) {
			self::showException($ex);
			return null;
		}
	}

	public static function decrypt($input, $key)
	{
		$key0 = hash('sha256', $key, true);
		try {
			$iv = pack('C*', 64, 64, 64, 64, 38, 38, 38, 38, 35, 35, 35, 35, 36, 36, 36, 36);
			$decrypted = openssl_decrypt($input, 'aes-256-cbc', $key0, 0, $iv);
			return $decrypted;
		} catch (Exception $ex) {
			self::showException($ex);
			return null;
		}
	}

	public static function validateGenerateCheckSumInput($key)
	{
		if ($key === null) {
			throw new Exception('Parameter cannot be null: Specified key');
		}
	}

	public static function validateVerifyCheckSumInput($checkSum, $key)
	{
		if ($key === null) {
			throw new Exception('Parameter cannot be null: Specified key');
		}
		if ($checkSum === null) {
			throw new Exception('Parameter cannot be null: Specified checkSum');
		}
	}

	public static function getStringByParams($parameters)
	{
		if ($parameters === null) {
			return '';
		}
		ksort($parameters);
		$stringBuilder = '';
		foreach ($parameters as $value) {
			$str = $value ?? '';
			$stringBuilder .= $str . '|';
		}
		return rtrim($stringBuilder, '|');
	}

	public static function generateRandomString($length)
	{
		if ($length <= 0) {
			return '';
		}
		$characters = '@#!abcdefghijklmonpqrstuvwxyz#@01234567890123456789#@ABCDEFGHIJKLMNOPQRSTUVWXYZ#@';
		$stringBuilder = '';
		for ($index = 0; $index < $length; $index++) {
			$startIndex = mt_rand(0, strlen($characters) - 1);
			$stringBuilder .= $characters[$startIndex];
		}
		return 'aaaa'; // $stringBuilder;
	}

	public static function getHashedString($inputValue)
	{
		return hash('sha256', $inputValue);
	}

	public static function showException($ex)
	{
		echo "Message: " . $ex->getMessage() . "\nStackTrace: " . $ex->getTraceAsString() . "\n";
	}
}
