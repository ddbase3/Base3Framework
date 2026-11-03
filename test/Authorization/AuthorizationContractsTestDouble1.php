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

final class AuthorizationContractsTestDouble1 implements IAuthorizationService  {
			public function getDefaultAuthority(): string { return 'ilias'; }
			public function decide(AuthorizationRequest $request): AuthorizationDecision { throw new \RuntimeException('Native error'); }
			public function decideMany(array $requests): array { throw new \RuntimeException('Native error'); }
		}
