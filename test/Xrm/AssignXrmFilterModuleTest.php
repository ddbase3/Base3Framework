<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class AssignXrmFilterModuleTest extends TestCase {

	public function testAssignedUserAndEmptyCases(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\AssignXrmFilterModule();
		$this->assertSame('assignxrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('assigned','to','user')));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('app','to','user')));
		$this->assertSame(['a','c'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('assigned','to','user'),true));
		$this->assertSame(['entry:c'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('assigned','to','user',1,1)));
		$x->userId=null;
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('assigned','to','user')));
		$x->userId='unassigned';
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('assigned','to','user')));
	}

	public function testRejectsUnknownAssignmentTarget(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\AssignXrmFilterModule();
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('assigned','to','everyone'),true));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
