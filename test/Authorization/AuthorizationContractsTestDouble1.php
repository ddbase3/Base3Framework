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
use Base3\Core\Authorization\AuthorizationMultiplexer;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Authorization\Dto\ResourceReference;
use Base3\Core\Authorization\No\NoAuthorizationService;
use Base3\CoreFoundation\Usermanager\Permission;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuthorizationContractsTestDouble1 implements IAuthorizationService  {
			public function getDefaultAuthority(): string { return 'ilias'; }
			public function decide(AuthorizationRequest $request): AuthorizationDecision { throw new \RuntimeException('Native error'); }
			public function decideMany(array $requests): array { throw new \RuntimeException('Native error'); }
		}
