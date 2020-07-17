<?php

class ModulbankHelper
{
	public static function calcSignature($secretKey, $params)
	{
		ksort($params);
		$chunks = array();
		foreach ($params as $key => $value) {
			$v = (string) $value;
			if (($v !== '') && ($key != 'signature')) {
				$chunks[] = $key . '=' . base64_encode($v);
			}
		}
		$signature = implode('&', $chunks);
		for ($i = 0; $i < 2; $i++) {
			$signature = sha1($secretKey . $signature);
		}
		return $signature;
	}

	public static function getSalt($length = 10)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$result     = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $result;
	}

	public static function refund($merchant, $amount, $transaction, $key)
	{
		$url  = 'https://pay.modulbank.ru/api/v1/refund';
		$data = [
			'merchant'       => $merchant,
			'amount'         => $amount,
			'transaction'    => $transaction,
			'unix_timestamp' => time(),
			'salt'           => self::getSalt(),
		];
		$data['signature'] = self::calcSignature($key, $data);
		$response = self::sendRequest('POST', $url, $data);
		return $response;
	}

	public static function capture($data, $key)
	{
		$url  = 'https://pay.modulbank.ru/api/v1/capture';
		$data['signature'] = self::calcSignature($key, $data);
		$response = self::sendRequest('POST', $url, $data);
		return $response;
	}

	public static function getTransactionStatus($merchant, $transaction, $key)
	{
		$url  = 'https://pay.modulbank.ru/api/v1/transaction';
		$data = [
			'merchant'       => $merchant,
			'transaction_id'    => $transaction,
			'unix_timestamp' => time(),
			'salt'           => self::getSalt(),
		];
		$data['signature'] = self::calcSignature($key, $data);
		$response = self::sendRequest('GET', $url, $data);
		return $response;
	}

	public static function sendRequest($method, $url, $data)
	{
		if ($method == 'GET') {
			$url .= "?".http_build_query($data);
		}
		$response = false;
		if (function_exists("curl_init")) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			if ($method == 'POST') {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			$response = curl_exec($ch);
			curl_close($ch);
		}
		return $response;
	}

	public static function log($fileBasePath, $message, $category , $maxFileSizeMb = 10, $maxLogFiles = 10, $fileMode = 0664, $dirMode = 0775 ) {
		$maxFileSize = $maxFileSizeMb / $maxLogFiles * 1024 * 1024;
		$filePath = $fileBasePath.".php";
		$dir = dirname($filePath);

		if (!is_dir($dir)) {
			@mkdir($dir, $dirMode);
			@chmod($dir, $dirMode);
		}

		$needToRotate = @filesize($filePath) > $maxFileSize;
		if ($needToRotate) {
			$file = $fileBasePath;

			for ($i = $maxLogFiles; $i >= 0; --$i) {
				$rotateFile = $file . ($i === 0 ? '' : '.' . $i). ".php";

				if (is_file($rotateFile)) {
					if ($i === $maxLogFiles) {
						@unlink($rotateFile);
					} else {
						@rename($rotateFile, $file . '.' . ($i + 1).".php");
					}
				}
			}
		}

		if (!file_exists($filePath)) {
			@touch($filePath);
			@file_put_contents($filePath, "#<?php die('Forbidden.'); ?>\n");
			chmod($filePath, $fileMode);
		}

		if (!is_scalar($message) ) {
			$message = var_export($message, true);
		}

		$line = sprintf('%s [%s]: %s', date('Y-m-d H:i:s'), $category, $message);
		$line .= "\n\n\n";

		@file_put_contents($filePath, $line, FILE_APPEND);
	}


	public static function sendPackedLogs($logDir)
	{
		if (!class_exists("ZipArchive")) {
			throw new Exception("You need to install php-zip", 1);

		}
		$zip  = new ZipArchive;
		$path = $logDir . '/modulbank' . uniqid() . '.zip';
		$res  = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		if ($res !== true) {
			throw new Exception("Can't create zip archive", 1);
		}
		if (function_exists("phpinfo")) {
			ob_start();
			phpinfo();
			$phpinfo = ob_get_clean();
			$zip->addFromString('phpinfo.html', $phpinfo);
		}
		$zip->addGlob($logDir . '/modulbank.log*', GLOB_BRACE, ['add_path' => DIRECTORY_SEPARATOR, 'remove_all_path' => true]);
		$zip->close();
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename="modulbank_logs.zip"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($path));
		readfile($path);
		unlink($path);
		exit();
	}

}

