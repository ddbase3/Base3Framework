<?php

namespace Microservice;

use Microservice\Api\IMicroservice;

abstract class AbstractMicroservice implements IMicroservice {

	// Implementation of IBase

	public function getName() {
		$c = get_class($this);
		return strtolower(substr($c, strrpos($c, '\\') + 1));
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {
		if ($out != "json" || !isset($_REQUEST["call"])) return null;

		$binarystream = isset($_REQUEST["binarystream"]) ? !!$_REQUEST["binarystream"] : false;

		$callparams = array();
		if (isset($_REQUEST["params"])) $_REQUEST["params"] = json_decode($_REQUEST["params"], 1);  // $params per JSON gesendet, da nur max. 1000 Parameter gesendet werden
		$params = isset($_REQUEST["params"]) ? $_REQUEST["params"] : array();

		$rm = new \ReflectionMethod(get_class($this), $_REQUEST["call"]);
		$parameters = $rm->getParameters();
		foreach ($parameters as $p) $callparams[] = isset($_REQUEST["params"][$p->name]) ? $_REQUEST["params"][$p->name] : null;

		$result = call_user_func_array(array($this, $_REQUEST["call"]), $callparams);
		return $binarystream ? $result : json_encode($result);
	}

	public function getHelp() {
		$out = '';
		$out .= '<p><b>' . static::class . '</b></p>';
		$out .= '<p>' . $this->getName() . '</p>';

		$methods = get_class_methods(static::class);

		$out .= '<ul>';
		foreach ($methods as $method) {
			if (in_array($method, array("__construct", "getName", "getOutput", "getHelp"))) continue;

			$params = array();
			$rm = new \ReflectionMethod(static::class, $method);
			$parameters = $rm->getParameters();
			foreach ($parameters as $p) $params[] = $p->name;

			$url = '/' . $this->getName() . '.json?call=' . $method;
			foreach ($params as $p) $url .= '&amp;params[' . $p . ']=xxxxx';

			$out .= '<li>' . $url . '</li>';
		}
		$out .= '</ul>';

		return $out;
	}

}
