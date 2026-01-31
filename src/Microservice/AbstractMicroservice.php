<?php declare(strict_types=1);

namespace Base3\Microservice;

use Base3\Microservice\Api\IMicroservice;

abstract class AbstractMicroservice implements IMicroservice {

	// Implementation of IBase

	public static function getName(): string {
		$fullClass = static::class;
		$parts = explode('\\', $fullClass);
		return strtolower(end($parts));
	}

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {
		if ($out != "json" || !isset($_REQUEST["call"])) return null;

		$binarystream = isset($_REQUEST["binarystream"]) ? !!$_REQUEST["binarystream"] : false;
		$serialized = isset($_REQUEST["serialized"]) ? !!$_REQUEST["serialized"] : false;

		$callparams = array();
		if (isset($_REQUEST["params"])) $_REQUEST["params"] = json_decode($_REQUEST["params"], true);  // $params per JSON gesendet, da nur max. 1000 Parameter gesendet werden
		$params = isset($_REQUEST["params"]) ? $_REQUEST["params"] : array();

		$rm = new \ReflectionMethod(get_class($this), $_REQUEST["call"]);
		$parameters = $rm->getParameters();
		foreach ($parameters as $p) $callparams[] = isset($_REQUEST["params"][$p->name]) ? $_REQUEST["params"][$p->name] : null;

		$result = call_user_func_array(array($this, $_REQUEST["call"]), $callparams);

		if ($binarystream) return $result;
		if ($serialized) return serialize($result);
		return json_encode($result);
	}

	public function getHelp(): string {
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
