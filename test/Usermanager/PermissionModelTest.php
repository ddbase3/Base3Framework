<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class PermissionModelTest extends TestCase {

	public function testFromArrayCopiesOnlyKnownFields(): void {
		$value = \Base3\Usermanager\Permission::fromArray(['id' => 17, 'scope' => 'course', 'permission' => 'read', 'label' => 'label-value', 'info' => 'info-value', 'archive' => 0, 'unexpected' => 'ignored']);
		$this->assertSame(17, $value->id);
		$this->assertSame('course', $value->scope);
		$this->assertSame('read', $value->permission);
		$this->assertSame('label-value', $value->label);
		$this->assertSame('info-value', $value->info);
		$this->assertSame(0, $value->archive);
		$this->assertFalse(property_exists($value, 'unexpected'));
	}

	public function testForFactoryBuildsScopeAndPermission(): void {
		$permission = \Base3\Usermanager\Permission::for('system', 'admin');
		$this->assertSame('system', $permission->scope);
		$this->assertSame('admin', $permission->permission);
	}

}
