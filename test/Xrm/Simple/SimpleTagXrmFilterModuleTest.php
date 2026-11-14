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

namespace Base3\Test\Xrm\Simple;

use PHPUnit\Framework\TestCase;

final class SimpleTagXrmFilterModuleTest extends TestCase {

	private function createModule(): array {
		$original=\Base3\Core\ServiceLocator::getInstance();
		$container=new \Base3\Core\ServiceLocator();
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$container->set('database',$db,\Base3\Core\ServiceLocator::SHARED);
		\Base3\Core\ServiceLocator::useInstance($container);
		try { $module=new \Base3\Core\Xrm\Simple\SimpleTagXrmFilterModule(); }
		finally { \Base3\Core\ServiceLocator::useInstance($original); }
		return [$module,$db];
	}

	public function testConnectAndDisconnectQueriesRespectEscapedTagAndPagination(): void {
		[$module,$db]=$this->createModule();
		$db->multiRows=[['uuid'=>'a'],['uuid'=>'b']];
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame(['a','b'],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn',"O'Reilly",null,4),true));
		$this->assertStringContainsString("`tag` = 'O\\'Reilly'",$db->multiQueries[0]);
		$this->assertStringContainsString('LIMIT 4',$db->multiQueries[0]);
		$this->assertSame(['entry:a','entry:b'],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','notconn','news',1,null)));
		$this->assertStringContainsString('LEFT JOIN `tag`',$db->multiQueries[1]);
		$this->assertStringContainsString('LIMIT 1, 18446744073709551615',$db->multiQueries[1]);
	}

	public function testUnsupportedOperatorsDoNotQueryDatabase(): void {
		[$module,$db]=$this->createModule();
		$this->assertSame('simpletagxrmfiltermodule',$module::getName());
		$this->assertSame(0,$module->match(new \stdClass(),new \Base3\CoreFoundation\Xrm\XrmFilter('tag','conn','news')));
		$xrm=new \Base3\Test\Xrm\FilterTestXrm();
		$this->assertSame([],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('tag','unknown','x'),true));
		$this->assertSame([],$module->getEntries($xrm,new \Base3\CoreFoundation\Xrm\XrmFilter('other','conn','x'),true));
		$this->assertSame(0,$db->connectCalls);
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
require_once __DIR__ . '/../FilterTestXrm.php';
