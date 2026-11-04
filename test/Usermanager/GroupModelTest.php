<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class GroupModelTest extends TestCase {

	public function testFromArrayCopiesOnlyKnownFields(): void {
		$value = \Base3\Usermanager\Group::fromArray(['id' => 17, 'name' => 'name-value', 'info' => 'info-value', 'archive' => 0, 'roles' => ['admin'], 'unexpected' => 'ignored']);
		$this->assertSame(17, $value->id);
		$this->assertSame('name-value', $value->name);
		$this->assertSame('info-value', $value->info);
		$this->assertSame(0, $value->archive);
		$this->assertSame(['admin'], $value->roles);
		$this->assertFalse(property_exists($value, 'unexpected'));
	}

}
