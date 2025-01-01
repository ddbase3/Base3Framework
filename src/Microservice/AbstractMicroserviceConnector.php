<?php declare(strict_types=1);

namespace Microservice;

use Microservice\Api\IMicroserviceConnector;
use Microservice\Api\IMicroserviceFlags;

abstract class AbstractMicroserviceConnector implements IMicroserviceConnector, IMicroserviceFlags {

	protected $url;
	protected $service;

	protected $servicelocator;
	protected $accesscontrol;
	protected $cnf;

	protected $flags;

	public function __construct($url, $service, $flags = 0) {
		$this->url = $url;
		$this->service = $service;
		$this->flags = $flags;

		$this->servicelocator = \Base3\ServiceLocator::getInstance();
		$this->accesscontrol = $this->servicelocator->get('accesscontrol');
		$configuration = $this->servicelocator->get('configuration');
		$this->cnf = $configuration == null ? array("masterpass" => "") : $configuration->get('microservice');
	}

	public function __call($method, $args) {

		$binarystream = $this->flags & self::BINARYSTREAM;

		$methoddata = $this->getMethodData($method);
		if (!$methoddata) die("method not found");

		$params = array();
		if ($methoddata != null && isset($methoddata["params"]))
			foreach ($methoddata["params"] as $k => $p)
				$params[$p] = isset($args[$k]) ? $args[$k] : null;

		// params per JSON gesendet, da nur max. 1000 Parameter gesendet werden (Array-Elemente werden einzeln gezählt!)
		$response = $this->httpPost(
			$this->url,
			array("call" => $method, "params" => json_encode($params), "binarystream" => $binarystream));

		return $binarystream ? $response : json_decode($response, true);
	}

	protected function getMethodData($method) {
		$methoddata = null;
		foreach ($this->service["methods"] as $m) {
			if ($m["name"] != $method) continue;
			$methoddata = $m;
			break;
		}
		return $methoddata;
	}

	protected function httpPost($url, $data) {
		$user = "internal";
		$pass = $this->cnf['masterpass'];
		$time = time();
		$token = $this->generateToken();
		$header = array(
			"user: " . urlencode((string) $user),
			"time: " . urlencode((string) $time),
			"token: " . urlencode((string) $token),
			"hash: " . urlencode(sha1($pass . $time . $token))
		);

		if (!($this->flags & self::INTERNALONLY)) {
			$autheduser = $this->accesscontrol == null ? null : $this->accesscontrol->getUserId();
			if ($autheduser != null) $header[] = "auth: " . urlencode($autheduser);
		}

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// only if https disabled
		// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	protected function generateToken($length = 32) {
		$characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
		$random_string = "";
		for ($p = 0; $p < $length; $p++) $random_string .= $characters[mt_rand(0, strlen($characters)-1)];
		return $random_string;
	}

	// Implementation of IMicroserviceConnector

	public function getMicroserviceUrl() {
		return $this->url;
	}
}
