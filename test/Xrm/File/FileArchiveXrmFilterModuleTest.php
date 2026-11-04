<?php declare(strict_types=1);

namespace Base3\Test\Xrm\File;

use PHPUnit\Framework\TestCase;

final class FileArchiveXrmFilterModuleTest extends TestCase {

	public function testRecognizesFileArchiveAndNeverGeneratesItsOwnEntries(): void {
		$x=new \Base3\Xrm\File\FileArchiveXrmFilterModule();$f=new \Base3\Xrm\XrmFilter('archive','eq',0);
		$this->assertSame('filearchivexrmfiltermodule',$x::getName());
		$this->assertSame(0,$x->match(new \stdClass(),$f));
		$this->assertSame([], $x->getEntries(new \stdClass(),$f,true));
	}

}
