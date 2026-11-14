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

namespace Base3\Test\Xrm\Base3;

use Base3\Core\ServiceLocator;
use Base3\Test\Support\RecordingDatabaseStub;
use Base3\Core\Xrm\Base3\Base3Xrm;
use PHPUnit\Framework\TestCase;

final class Base3XrmTest extends TestCase {

	private function createBase3Xrm(bool $withDatabase=true): array {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		$db=new RecordingDatabaseStub();
		if($withDatabase)$container->set('database',$db,ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { $xrm=new Base3Xrm('core'); }
		finally { ServiceLocator::useInstance($previous); }
		return [$xrm,$db];
	}

	public function testXrmNameAndDatabaseDependencyState(): void {
		[$xrm]=$this->createBase3Xrm();
		$this->assertSame('core',$xrm->getXrmName());
		$this->assertSame(['depending_services'=>'Ok'],$xrm->checkDependencies());
		[$missing]=$this->createBase3Xrm(false);
		$this->assertSame(['depending_services'=>'Fail'],$missing->checkDependencies());
	}

	public function testPseudoIdsAreRejectedBeforeDatabaseAccess(): void {
		[$xrm,$db]=$this->createBase3Xrm();
		$this->assertFalse($xrm->setEntry((object)['id'=>'xxtaexample']));
		$this->assertSame([], $db->nonQueries);
	}
}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
