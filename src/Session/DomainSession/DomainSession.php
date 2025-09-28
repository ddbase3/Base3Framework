<?php declare(strict_types=1);

namespace Base3\Session\DomainSession;

use Base3\Session\AbstractSession;
use Base3\Configuration\Api\IConfiguration;

class DomainSession extends AbstractSession {

	public function __construct(
		private readonly IConfiguration $configuration
	) {}

	public function start(): bool {
		if ($this->isStarted) {
			return true;
		}

		if (PHP_SAPI === 'cli') {
			return false;
		}

		$config = array_merge([
			"extensions" => [],
			"cookiedomain" => ""
		], $this->configuration->get('session') ?? []);

		if (!empty($config["cookiedomain"])) {
			ini_set('session.cookie_domain', $config["cookiedomain"]);
		}

		if (session_status() === PHP_SESSION_NONE) {
			if (!session_start()) {
				return false;
			}
		}

		$this->isStarted = true;
		return true;
	}
}

