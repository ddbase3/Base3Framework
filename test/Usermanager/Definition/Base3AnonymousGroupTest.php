<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Definition;

use PHPUnit\Framework\TestCase;

final class Base3AnonymousGroupTest extends TestCase {

	public function testLogicalAnonymousGroupHasFixedNonEditableCriteria(): void {
		$group=new \Base3\Usermanager\Definition\Base3AnonymousGroup();
		$this->assertSame('base3anonymousgroup',$group::getName());
		$this->assertSame('anonymous',$group->getGroup()->name);
		$this->assertSame(0,$group->getGroup()->archive);
		$this->assertFalse($group->isEditable());
		$this->assertSame(['criterion'=>'base3anonymouscriterion','config'=>[]],$group->getCriteria());
	}

}
