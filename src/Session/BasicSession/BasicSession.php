<?php declare(strict_types=1);

namespace Base3\Session\BasicSession;

use Base3\Session\Api\ISession;
use Base3\Configuration\Api\IConfiguration;

class BasicSession implements ISession {

	private bool $isStarted = false;

	public function __construct(
		private readonly IConfiguration $configuration
	) {}

	public function start(): void {
		if ($this->isStarted) return;

		if (PHP_SAPI === 'cli') return;

		$config = array_merge([
			"extensions" => [],
			"cookiedomain" => ""
		], $this->configuration->get('session') ?? []);

		// $_REQUEST['out'] nicht mehr gesetzt, also deaktiviert (wegen RoutungServiceSelector)
		// $out = $_REQUEST['out'] ?? null;
		// if (!in_array($out, $config['extensions'])) return;

		if (session_status() === PHP_SESSION_NONE) session_start();
		$this->isStarted = true;
	}

	public function started(): bool {
		return $this->isStarted;
	}
}

