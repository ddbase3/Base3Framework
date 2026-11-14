<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase {

	public function testFactoryPreservesOnlyContractFields(): void {
		$model=\Base3\CoreFoundation\Usermanager\User::fromArray(['id'=>'id-value', 'userid'=>'userid-value', 'name'=>'name-value', 'email'=>'email-value', 'lang'=>'lang-value', 'role'=>'role-value', 'roles'=>'roles-value', 'unexpected'=>'secret']);
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
		$model=\Base3\CoreFoundation\Usermanager\User::fromArray([]);
		$this->assertNull($model->id);
		$this->assertSame([],$model->roles);
	}

}
