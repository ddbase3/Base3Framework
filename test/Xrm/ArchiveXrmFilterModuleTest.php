<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class ArchiveXrmFilterModuleTest extends TestCase {

	public function testUndefinedArchiveAndSlicing(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\ArchiveXrmFilterModule();
		$this->assertSame('archivexrmfiltermodule',$m::getName());
		$this->assertSame(1,$m->match($x,new \Base3\Xrm\XrmFilter('archive','eq','undef')));
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('name','eq','x')));
		$this->assertSame(['a','b','c','d'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','eq','undef'),true));
		$this->assertSame(['entry:b','entry:c'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','eq','undef',1,2)));
		$this->assertSame(['entry:c','entry:d'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','eq','undef',2)));
		$this->assertSame(['entry:a'],$m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','eq','undef',null,1)));
	}

	public function testUnknownArchiveComparisons(): void {
		$x=new FilterTestXrm();$m=new \Base3\Xrm\ArchiveXrmFilterModule();
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','eq',1),true));
		$this->assertSame([], $m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','neq','undef')));
		$this->assertSame(['a','b','c','d'], $m->getEntries($x,new \Base3\Xrm\XrmFilter('archive','neq',1),true));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
