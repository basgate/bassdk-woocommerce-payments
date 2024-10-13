<?php
if (!class_exists('BasgateChecksum')) :
	class BasgateChecksum
	{
		public static function version()
		{
			return "1.0.0.0";
		}

		public static function generateSignature(array $input, string $key): ?string
		{
			return self::generateSignature2(self::getStringByParams($input), $key);
		}

		public static function generateSignature2(string $input, string $key): ?string
		{
			try {
				self::validateGenerateCheckSumInput($key);
				$stringBuilder = $input . "|";
				$randomString = self::generateRandomString(4);
				$stringBuilder .= $randomString;
				$hash = self::getHashedString($stringBuilder);
				$hashRandom = $hash . $randomString;
				return self::encrypt($hashRandom, $key);
			} catch (\Exception $ex) {
				self::showException($ex);
				return null;
			}
		}

		public static function verifySignature(array $input, string $key, string $checkSum): bool
		{
			return self::verifySignature2(self::getStringByParams($input), $key, $checkSum);
		}

		public static function verifySignature2(string $input, string $key, string $checkSum): bool
		{
			try {
				self::validateVerifyCheckSumInput($checkSum, $key);
				$str1 = self::decrypt($checkSum, $key);
				if ($str1 === null || strlen($str1) < 4) {
					return false;
				}
				$str2 = substr($str1, -4);
				$stringBuilder = $input . "|" . $str2;
				$source = self::getHashedString($stringBuilder);
				return $str1 === $source . $str2;
			} catch (\Exception $ex) {
				self::showException($ex);
				return false;
			}
		}

		public static function verifySignatureOrThrow(string $input, string $key, string $checkSum): bool
		{
			self::validateVerifyCheckSumInput($checkSum, $key);
			$str1 = self::decryptOrThrow($checkSum, $key);
			if ($str1 === null || strlen($str1) < 4) {
				return false;
			}
			$str2 = substr($str1, -4);
			$stringBuilder = $input . "|" . $str2;
			$source = self::getHashedString($stringBuilder);
			return $str1 === $source . $str2;
		}

		public static function encrypt(string $input, string $key): ?string
		{
			$key0 = hash('sha256', $key, true);
			try {
				$iv = str_repeat(chr(64), 16);
				$cipher = openssl_encrypt($input, 'aes-256-cbc', $key0, OPENSSL_RAW_DATA, $iv);
				return base64_encode($cipher);
			} catch (\Exception $ex) {
				self::showException($ex);
				return null;
			}
		}

		public static function decrypt(string $input, string $key): ?string
		{
			$key0 = hash('sha256', $key, true);
			try {
				$iv = str_repeat(chr(64), 16);
				$decrypted = openssl_decrypt(base64_decode($input), 'aes-256-cbc', $key0, OPENSSL_RAW_DATA, $iv);
				return $decrypted;
			} catch (\Exception $ex) {
				self::showException($ex);
				return null;
			}
		}

		public static function decryptOrThrow(string $input, string $key): string
		{
			$key0 = hash('sha256', $key, true);
			$iv = str_repeat(chr(64), 16);
			$decrypted = openssl_decrypt(base64_decode($input), 'aes-256-cbc', $key0, OPENSSL_RAW_DATA, $iv);
			return $decrypted;
		}

		private static function validateGenerateCheckSumInput(string $key): void
		{
			if ($key === null) {
				throw new \InvalidArgumentException("Parameter cannot be null: Specified key");
			}
		}

		private static function validateVerifyCheckSumInput(string $checkSum, string $key): void
		{
			if ($key === null) {
				throw new \InvalidArgumentException("Parameter cannot be null: Specified key");
			}
			if ($checkSum === null) {
				throw new \InvalidArgumentException("Parameter cannot be null: Specified checkSum");
			}
		}

		private static function getStringByParams(array $parameters): string
		{
			if ($parameters === null) {
				return "";
			}
			ksort($parameters);
			return implode('|', array_map(function ($value) {
				return $value ?? "";
			}, $parameters));
		}

		private static function generateRandomString(int $length): string
		{
			if ($length <= 0) {
				return "";
			}
			$characters = "@#!abcdefghijklmonpqrstuvwxyz#@01234567890123456789#@ABCDEFGHIJKLMNOPQRSTUVWXYZ#@";
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[random_int(0, strlen($characters) - 1)];
			}
			return $randomString;
		}

		private static function getHashedString(string $inputValue): string
		{
			return strtolower(bin2hex(hash('sha256', $inputValue, true)));
		}

		private static function showException(\Exception $ex): void
		{
			echo "Message : " . $ex->getMessage() . PHP_EOL . "StackTrace : " . $ex->getTraceAsString();
		}
	}
endif;
