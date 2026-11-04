<?php declare(strict_types=1);

namespace Base3\Test\Core;

use Base3\Api\IComponent;

final class ResolverTestComponent implements IComponent {
	public static function getName(): string { return 'resolver-test-component'; }
	public function __construct(private readonly string $instanceId) {}
	public function id(): string { return $this->instanceId; }
}
