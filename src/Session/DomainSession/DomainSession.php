<?php declare(strict_types=1);

namespace Base3\Session\DomainSession;

use Base3\Session\Api\ISession;
use Base3\Configuration\Api\IConfiguration;

class DomainSession implements ISession {

	private $started = false;

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
		if (!isset($_REQUEST['out']) || !in_array($_REQUEST['out'], $cnf["extensions"])) return;
		// cross subdomain session cookie
		if (strlen($cnf["cookiedomain"])) ini_set('session.cookie_domain', $cnf["cookiedomain"]);

		session_start();
		$this->started = true;
	}

	public function started(): bool {
		return $this->started;
	}
}
