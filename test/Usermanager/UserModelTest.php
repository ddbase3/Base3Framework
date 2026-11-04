<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase {

	public function testFromArrayCopiesOnlyKnownFields(): void {
		$value = \Base3\Usermanager\User::fromArray(['id' => 17, 'userid' => 'joe', 'name' => 'name-value', 'email' => 'joe@example.org', 'lang' => 'de', 'role' => 'admin', 'roles' => ['admin'], 'unexpected' => 'ignored']);
		$this->assertSame(17, $value->id);
		$this->assertSame('joe', $value->userid);
		$this->assertSame('name-value', $value->name);
		$this->assertSame('joe@example.org', $value->email);
		$this->assertSame('de', $value->lang);
		$this->assertSame('admin', $value->role);
		$this->assertSame(['admin'], $value->roles);
		$this->assertFalse(property_exists($value, 'unexpected'));
	}

}
