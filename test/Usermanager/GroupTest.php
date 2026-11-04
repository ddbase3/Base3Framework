<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use PHPUnit\Framework\TestCase;

final class GroupTest extends TestCase {

	public function testFactoryPreservesOnlyContractFields(): void {
		$model=\Base3\Usermanager\Group::fromArray(['id'=>'id-value', 'name'=>'name-value', 'info'=>'info-value', 'archive'=>'archive-value', 'roles'=>'roles-value', 'unexpected'=>'secret']);
		$this->assertSame('id-value',$model->id);
		$this->assertSame('name-value',$model->name);
		$this->assertSame('info-value',$model->info);
		$this->assertSame('archive-value',$model->archive);
		$this->assertSame('roles-value',$model->roles);
		$this->assertFalse(isset($model->unexpected));
	}

	public function testMissingFieldsKeepDeclaredDefaults(): void {
		$model=\Base3\Usermanager\Group::fromArray([]);
		$this->assertNull($model->id);
		$this->assertSame([],$model->roles);
	}

}
