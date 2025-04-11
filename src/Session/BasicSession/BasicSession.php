<?php declare(strict_types=1);

namespace Base3\Session\BasicSession;

use Base3\Session\Api\ISession;
use Base3\Configuration\Api\IConfiguration;

class BasicSession implements ISession {

	private $isStarted = false;

	public function __construct(IConfiguration $configuration) {

		$defaultConfig = [
			"extensions" => array(),
			"cookiedomain" => ""
		];

		$cnf = array_merge(
			$defaultConfig,
			$configuration->get('session'));

		// for testing
		if (php_sapi_name() === 'cli') return;

		// only create session, if chosen output is one of the session extensions
		// TODO check - was ist, wenn csv geladen werden soll und das nur per Session gemacht werden kann?
		if (!isset($_REQUEST['out']) || !in_array($_REQUEST['out'], $cnf["extensions"])) return;

		session_start();
		$this->isStarted = true;
	}

	public function started(): bool {
		return $this->isStarted;
	}
}
