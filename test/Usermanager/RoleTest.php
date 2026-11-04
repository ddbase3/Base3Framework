<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase {

	public function testFactoryPreservesOnlyContractFields(): void {
		$model=\Base3\Usermanager\Role::fromArray(['id'=>'id-value', 'name'=>'name-value', 'label'=>'label-value', 'info'=>'info-value', 'archive'=>'archive-value', 'permissions'=>'permissions-value', 'unexpected'=>'secret']);
		$this->assertSame('id-value',$model->id);
		$this->assertSame('name-value',$model->name);
		$this->assertSame('label-value',$model->label);
		$this->assertSame('info-value',$model->info);
		$this->assertSame('archive-value',$model->archive);
		$this->assertSame('permissions-value',$model->permissions);
		$this->assertFalse(isset($model->unexpected));
	}

	public function testMissingFieldsKeepDeclaredDefaults(): void {
		$model=\Base3\Usermanager\Role::fromArray([]);
		$this->assertNull($model->id);
		$this->assertSame([],$model->permissions);
	}

	public function testNamedFactoryProvidesExactScopeOrName(): void {
		$named=\Base3\Usermanager\Role::named('editor');
		$this->assertSame('editor',$named->name);
		$this->assertSame([],$named->permissions);
	}

}
