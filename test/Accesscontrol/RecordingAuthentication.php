<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol;

use Base3\Accesscontrol\Api\IAuthentication;

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
