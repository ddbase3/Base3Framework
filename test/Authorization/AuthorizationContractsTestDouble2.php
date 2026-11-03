<?php declare(strict_types=1);

namespace Base3\Test\Authorization;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\AuthorizationMultiplexer;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Authorization\Dto\ResourceReference;
use Base3\Authorization\No\NoAuthorizationService;
use Base3\Usermanager\Permission;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuthorizationContractsTestDouble2 implements IAuthorizationService {
			public function __construct(private string $authority, private bool $allowed) {}
			public function getDefaultAuthority(): string { return $this->authority; }
			public function decide(AuthorizationRequest $request): AuthorizationDecision {
				if ($request->authority !== $this->authority) return AuthorizationDecision::indeterminate('unknown_authority');
				return $this->allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
			}
			public function decideMany(array $requests): array {
				$out = [];
				foreach ($requests as $key => $request) $out[$key] = $this->decide($request);
				return $out;
			}
		}
