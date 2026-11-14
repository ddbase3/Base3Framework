<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Accesscontrol;

use Base3\CoreFoundation\Accesscontrol\Api\IAuthentication;

final class RecordingAuthentication implements IAuthentication {
	public array $lifecycle = [];
	public bool $verbose = false;
	public function __construct(private readonly ?string $identity) {}
	public static function getName(): string { return 'recordingauthentication'; }
	public function setVerbose($verbose) { $this->verbose=(bool)$verbose; }
	public function login(?array $credentials = null) {
		$this->lifecycle[]='login';
		return $credentials === null ? $this->identity : null;
	}
	public function keep($userid) { $this->lifecycle[]='keep:'.$userid; }
	public function finish($userid) { $this->lifecycle[]='finish:'.($userid ?? 'null'); }
	public function logout() { $this->lifecycle[]='logout'; }
}
