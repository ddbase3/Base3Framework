<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class RoleModelTest extends TestCase {

	public function testFromArrayCopiesOnlyKnownFields(): void {
		$value = \Base3\Usermanager\Role::fromArray(['id' => 17, 'name' => 'name-value', 'label' => 'label-value', 'info' => 'info-value', 'archive' => 0, 'permissions' => ['read'], 'unexpected' => 'ignored']);
		$this->assertSame(17, $value->id);
		$this->assertSame('name-value', $value->name);
		$this->assertSame('label-value', $value->label);
		$this->assertSame('info-value', $value->info);
		$this->assertSame(0, $value->archive);
		$this->assertSame(['read'], $value->permissions);
		$this->assertFalse(property_exists($value, 'unexpected'));
	}

	public function testNamedFactorySetsOnlyRoleName(): void {
		$role = \Base3\Usermanager\Role::named('editor');
		$this->assertSame('editor', $role->name);
		$this->assertSame([], $role->permissions);
	}

}
