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

namespace Base3\Test\Authorization;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;

final class NativeAuthorizationTestService implements IAuthorizationService {

	public bool $throwSingle = false;
	public bool $throwBatch = false;
	public ?array $batchReturn = null;
	public array $singleRequests = [];
	public array $batches = [];

	public function __construct(private readonly string $authority) {}

	public function getDefaultAuthority(): string { return $this->authority; }

	public function decide(AuthorizationRequest $request): AuthorizationDecision {
		$this->singleRequests[] = $request;
		if ($this->throwSingle) throw new \RuntimeException('Native authority unavailable');
		return AuthorizationDecision::allow($this->authority);
	}

	public function decideMany(array $requests): array {
		$this->batches[] = $requests;
		if ($this->throwBatch) throw new \RuntimeException('Native batch unavailable');
		if ($this->batchReturn !== null) return $this->batchReturn;
		$results = [];
		foreach ($requests as $key => $_) $results[$key] = AuthorizationDecision::allow($this->authority);
		return $results;
	}
}
