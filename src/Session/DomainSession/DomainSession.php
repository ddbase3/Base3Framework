<?php declare(strict_types=1);

namespace Base3\Session\DomainSession;

use Base3\Session\Api\ISession;
use Base3\Configuration\Api\IConfiguration;

class DomainSession implements ISession {

	private bool $started = false;

	public function __construct(
		private readonly IConfiguration $configuration
	) {}

	public function start(): void {
		if ($this->started || PHP_SAPI === 'cli') return;

		$config = array_merge([
			"extensions" => [],
			"cookiedomain" => ""
		], $this->configuration->get('session') ?? []);

		// $_REQUEST['out'] nicht mehr gesetzt, also deaktiviert (wegen RoutungServiceSelector)
		// $out = $_REQUEST['out'] ?? null;
		// if (!in_array($out, $config['extensions'])) return;

		if (!empty($config["cookiedomain"])) {
			ini_set('session.cookie_domain', $config["cookiedomain"]);
		}

		if (session_status() === PHP_SESSION_NONE) session_start();
		$this->started = true;
	}

	public function started(): bool {
		return $this->started;
	}
}

