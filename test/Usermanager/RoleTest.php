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

final class RoleTest extends TestCase {

	public function testFactoryPreservesOnlyContractFields(): void {
		$model=\Base3\CoreFoundation\Usermanager\Role::fromArray(['id'=>'id-value', 'name'=>'name-value', 'label'=>'label-value', 'info'=>'info-value', 'archive'=>'archive-value', 'permissions'=>'permissions-value', 'unexpected'=>'secret']);
		$this->assertSame('id-value',$model->id);
		$this->assertSame('name-value',$model->name);
		$this->assertSame('label-value',$model->label);
		$this->assertSame('info-value',$model->info);
		$this->assertSame('archive-value',$model->archive);
		$this->assertSame('permissions-value',$model->permissions);
		$this->assertFalse(isset($model->unexpected));
	}

	public function testMissingFieldsKeepDeclaredDefaults(): void {
		$model=\Base3\CoreFoundation\Usermanager\Role::fromArray([]);
		$this->assertNull($model->id);
		$this->assertSame([],$model->permissions);
	}

	public function testNamedFactoryProvidesExactScopeOrName(): void {
		$named=\Base3\CoreFoundation\Usermanager\Role::named('editor');
		$this->assertSame('editor',$named->name);
		$this->assertSame([],$named->permissions);
	}

}
