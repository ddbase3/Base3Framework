<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IContainer;
use PHPUnit\Framework\TestCase;

final class InvokableWithParam {
	public function __invoke(ServiceLocator $container): DummyWithContainer {
		return new DummyWithContainer($container);
	}
}
