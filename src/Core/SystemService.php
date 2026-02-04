<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\ISystemService;

/**
 * Default system service for standalone BASE3 installations.
 *
 * In standalone mode BASE3 acts as both host system and embedded system.
 * Therefore, host and embedded system name/version are identical.
 *
 * This implementation is intentionally minimal and deterministic.
 */
final class SystemService implements ISystemService {

	/**
	 * Returns the name of the host system.
	 *
	 * @return string
	 */
	public function getHostSystemName() : string {
		return 'BASE3';
	}

	/**
	 * Returns the version of the host system.
	 *
	 * @return string
	 */
	public function getHostSystemVersion() : string {
		return $this->getBase3Version();
	}

	/**
	 * Returns the name of the embedded system.
	 *
	 * @return string
	 */
	public function getEmbeddedSystemName() : string {
		return 'BASE3';
	}

	/**
	 * Returns the version of the embedded system.
	 *
	 * @return string
	 */
	public function getEmbeddedSystemVersion() : string {
		return $this->getBase3Version();
	}

	/**
	 * Reads the BASE3 version from the VERSION file in DIR_ROOT.
	 *
	 * Rules:
	 * - If DIR_ROOT is not defined, return "".
	 * - If the VERSION file is missing or unreadable, return "".
	 * - The file is expected to contain the version as a single line (e.g. "4.0.1").
	 * - Content is trimmed; empty result returns "".
	 * - No exceptions are thrown and no warnings should be emitted.
	 */
	protected function getBase3Version() : string {
		if (!defined('DIR_ROOT')) return '';

		$path = rtrim((string) DIR_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'VERSION';
		if (!is_file($path) || !is_readable($path)) return '';

		$content = @file_get_contents($path);
		if ($content === false) return '';

		$version = trim($content);
		if ($version === '') return '';

		// Minimal safety: avoid returning multi-line or obviously broken content.
		if (str_contains($version, "\n") || str_contains($version, "\r")) {
			$version = trim(strtok($version, "\r\n"));
			if ($version === '') return '';
		}

		return $version;
	}
}
