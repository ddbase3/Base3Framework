<?php declare(strict_types=1);

namespace Base3\Token\FileToken;

use Base3\Token\Api\IToken;
use Base3\Api\ICheck;

class FileToken implements IToken, ICheck {

	private $dir;

	public function __construct() {
		$this->dir = DIR_LOCAL . DIRECTORY_SEPARATOR . "FileToken" . DIRECTORY_SEPARATOR;

		if (!is_dir($this->dir)) mkdir($this->dir);
	}

	private function getFilename($scope, $id) {
		return $this->dir . preg_replace('/[^\w]/', '', $scope) . "-" . md5($id) . ".json";
	}

	private function toFile($filename, $data) {
		$fp = fopen($filename, "w");
		fwrite($fp, json_encode($data));
		fclose($fp);
	}

	// Implementation of IToken

	public function create($scope, $id, $size = 32, $duration = 3600) {
		$filename = $this->getFilename($scope, $id);
		$time = time();

		$token = bin2hex(openssl_random_pseudo_bytes($size));
		$timeout = $time + $duration;

		$data = array();
		if (file_exists($filename)) {
			$content = file_get_contents($filename);
			if (strlen($content)) $data = json_decode($content, true);
		}
		foreach ($data as $key => $val) if ($val["timeout"] < $time) unset($data[$key]);
		$data[] = array("token" => sha1($token), "timeout" => $timeout);

		$this->toFile($filename, $data);

		return $token;
	}

	public function save($scope, $id, $token, $duration = 3600) {
		$filename = $this->getFilename($scope, $id);
		$time = time();

		$timeout = $time + $duration;

		$data = array();
		if (file_exists($filename)) {
			$content = file_get_contents($filename);
			if (strlen($content)) $data = json_decode($content, true);
		}
		foreach ($data as $key => $val) if ($val["timeout"] < $time) unset($data[$key]);
		$data[] = array("token" => sha1($token), "timeout" => $timeout);

		$this->toFile($filename, $data);
	}

	public function check($scope, $id, $token) {
		$filename = $this->getFilename($scope, $id);
		if (!file_exists($filename)) return false;

		$time = time();
		$hash = sha1($token);

		$data = array();
		$content = file_get_contents($filename);
		if (strlen($content)) $data = json_decode($content, true);
		foreach ($data as $key => $val) if ($val["timeout"] < $time) unset($data[$key]);

		foreach ($data as $key => $val) if ($val["token"] == $hash) return true;
		return false;
	}

	public function delete($scope, $id, $token) {
		$filename = $this->getFilename($scope, $id);
		if (!file_exists($filename)) return;

		$time = time();
		$hash = sha1($token);

		$data = array();
		$content = file_get_contents($filename);
		if (strlen($content)) $data = json_decode($content, true);

		foreach ($data as $key => $val) {
			if ($val["timeout"] < $time) unset($data[$key]);
			if ($val["token"] == $hash) unset($data[$key]);
		}

		$this->toFile($filename, $data);
	}

	public function clean($scope, $id) {
		$filename = $this->getFilename($scope, $id);
		unlink($filename);
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"openssl_available" => extension_loaded('openssl') ? "Ok" : "OpenSSL extension not loaded",
			"filetoken_dir_writable" => is_dir(DIR_LOCAL . 'FileToken') && is_writable(DIR_LOCAL . 'FileToken') ? "Ok" : "filetoken dir not writable"
		);
	}

}
