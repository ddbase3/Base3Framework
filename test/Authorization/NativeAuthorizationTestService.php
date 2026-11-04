<?php declare(strict_types=1);

namespace Base3\Test\Authorization;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;

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
