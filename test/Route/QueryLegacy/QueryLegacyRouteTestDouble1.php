<?php declare(strict_types=1);

namespace Base3\Test\Route\QueryLegacy;

use PHPUnit\Framework\TestCase;
use Base3\Route\QueryLegacy\QueryLegacyRoute;
use Base3\Api\IContainer;
use Base3\Api\IRequest;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Api\IClassMap;
use Base3\Api\IOutput;
use Base3\Test\Core\ClassMapStub;
use Base3\Test\Core\ContainerStub;
use Base3\Test\Configuration\ConfigurationStub;
use Base3\Test\Core\OutputStub;

final class QueryLegacyRouteTestDouble1 implements IAccesscontrol {
			public function __construct(private string $userId) {}
			public function getUserId() { return $this->userId; }
			public function authenticate(): void {}
		}
