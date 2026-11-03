<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class DummyWithContainer {
	public ServiceLocator $container;

	public function __construct(ServiceLocator $container) {
		$this->container = $container;
	}
}
