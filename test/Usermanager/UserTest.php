<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase {

	public function testFactoryPreservesOnlyContractFields(): void {
		$model=\Base3\Usermanager\User::fromArray(['id'=>'id-value', 'userid'=>'userid-value', 'name'=>'name-value', 'email'=>'email-value', 'lang'=>'lang-value', 'role'=>'role-value', 'roles'=>'roles-value', 'unexpected'=>'secret']);
		$this->assertSame('id-value',$model->id);
		$this->assertSame('userid-value',$model->userid);
		$this->assertSame('name-value',$model->name);
		$this->assertSame('email-value',$model->email);
		$this->assertSame('lang-value',$model->lang);
		$this->assertSame('role-value',$model->role);
		$this->assertSame('roles-value',$model->roles);
		$this->assertFalse(isset($model->unexpected));
	}

	public function testMissingFieldsKeepDeclaredDefaults(): void {
		$model=\Base3\Usermanager\User::fromArray([]);
		$this->assertNull($model->id);
		$this->assertSame([],$model->roles);
	}

}
