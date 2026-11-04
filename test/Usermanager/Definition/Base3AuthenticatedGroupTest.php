<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Definition;

use PHPUnit\Framework\TestCase;

final class Base3AuthenticatedGroupTest extends TestCase {

	public function testLogicalAuthenticatedGroupHasFixedNonEditableCriteria(): void {
		$group=new \Base3\Usermanager\Definition\Base3AuthenticatedGroup();
		$this->assertSame('base3authenticatedgroup',$group::getName());
		$this->assertSame('authenticated',$group->getGroup()->name);
		$this->assertSame(0,$group->getGroup()->archive);
		$this->assertFalse($group->isEditable());
		$this->assertSame(['criterion'=>'base3authenticatedcriterion','config'=>[]],$group->getCriteria());
	}

}
