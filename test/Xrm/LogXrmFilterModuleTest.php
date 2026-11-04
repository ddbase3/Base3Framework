<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class LogXrmFilterModuleTest extends TestCase {

	public function testMatchesSupportedLogAttributesOnly(): void {
		$m=new \Base3\Xrm\LogXrmFilterModule();$x=new FilterTestXrm();
		$this->assertSame('logxrmfiltermodule',$m::getName());
		foreach (['owner','created','changed'] as $attr) {
			$f=new \Base3\Xrm\XrmFilter($attr,'eq','now');
			$this->assertSame(1,$m->match($x,$f));
			$this->assertSame([],$m->getEntries($x,$f));
		}
		$this->assertSame(0,$m->match($x,new \Base3\Xrm\XrmFilter('archive','eq',0)));
	}

}

require_once __DIR__ . '/FilterTestXrm.php';
