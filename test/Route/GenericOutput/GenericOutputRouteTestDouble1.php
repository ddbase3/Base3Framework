<?php declare(strict_types=1);

namespace Base3\Test\Route\GenericOutput;

use PHPUnit\Framework\TestCase;
use Base3\Route\GenericOutput\GenericOutputRoute;
use Base3\Configuration\Api\IConfiguration;
use Base3\Accesscontrol\Api\IAccesscontrol;
use Base3\Api\IClassMap;
use Base3\Api\IOutput;
use Base3\Test\Core\ClassMapStub;
use Base3\Test\Configuration\ConfigurationStub;
use Base3\Test\Core\OutputStub;

final class GenericOutputRouteTestDouble1 implements IAccesscontrol {
			public function __construct(private string $userId) {}
			public function getUserId() { return $this->userId; }
			public function authenticate(): void {}
		}
