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

namespace Base3\Test\Route\QueryLegacy;

use PHPUnit\Framework\TestCase;
use Base3\Core\Route\QueryLegacy\QueryLegacyRoute;
use Base3\CoreFoundation\Api\IContainer;
use Base3\CoreFoundation\Api\IRequest;
use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\Accesscontrol\Api\IAccesscontrol;
use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Api\IOutput;
use Base3\Test\Core\ClassMapStub;
use Base3\Test\Core\ContainerStub;
use Base3\Test\Configuration\ConfigurationStub;
use Base3\Test\Core\OutputStub;

final class QueryLegacyRouteTestDouble1 implements IAccesscontrol {
			public function __construct(private string $userId) {}
			public function getUserId() { return $this->userId; }
			public function authenticate(): void {}
		}
