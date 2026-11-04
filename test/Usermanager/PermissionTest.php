<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase {

	public function testFactoryPreservesOnlyContractFields(): void {
		$model=\Base3\Usermanager\Permission::fromArray(['id'=>'id-value', 'scope'=>'scope-value', 'permission'=>'permission-value', 'label'=>'label-value', 'info'=>'info-value', 'archive'=>'archive-value', 'unexpected'=>'secret']);
		$this->assertSame('id-value',$model->id);
		$this->assertSame('scope-value',$model->scope);
		$this->assertSame('permission-value',$model->permission);
		$this->assertSame('label-value',$model->label);
		$this->assertSame('info-value',$model->info);
		$this->assertSame('archive-value',$model->archive);
		$this->assertFalse(isset($model->unexpected));
	}

	public function testMissingFieldsKeepDeclaredDefaults(): void {
		$model=\Base3\Usermanager\Permission::fromArray([]);
		$this->assertNull($model->id);
	}

	public function testNamedFactoryProvidesExactScopeOrName(): void {
		$named=\Base3\Usermanager\Permission::for('courses','read');
		$this->assertSame('courses',$named->scope);
		$this->assertSame('read',$named->permission);
		$this->assertNull($named->id);
	}

}
