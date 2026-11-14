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

namespace Base3\Test\Xrm\File;

use Base3\Core\ServiceLocator;
use Base3\Test\Xrm\Cache\CacheUsermanagerFixture;
use Base3\Core\Xrm\File\FileXrm;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FileXrmTest extends TestCase {

	private string $localDir;

	protected function setUp(): void {
		$this->localDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR . 'base3_filexrm_test_' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		if (!mkdir($this->localDir, 0700, true) && !is_dir($this->localDir)) {
			self::fail('Could not create an isolated FileXrm test directory');
		}
		define('Base3\\Core\\Xrm\\File\\DIR_LOCAL', $this->localDir);
	}

	protected function tearDown(): void {
		if (is_dir($this->localDir)) rmdir($this->localDir);
	}

	private function createFileXrm(): FileXrm {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		$container->set('usermanager',new CacheUsermanagerFixture(),ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new FileXrm('documents'); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testUnknownFileEntriesStayAbsentWithoutWriting(): void {
		$xrm=$this->createFileXrm();
		$id=bin2hex(random_bytes(16));
		$this->assertSame('documents',$xrm->getXrmName());
		$this->assertNull($xrm->getEntry($id));
		$this->assertSame([],$xrm->getEntries([$id]));
		$this->assertFalse($xrm->delEntry($id));
		$this->assertSame([],$xrm->checkDependencies());
	}

	public function testUnauthorizedUserCannotEnumerateFiles(): void {
		$xrm=$this->createFileXrm();
		$this->assertSame([],$xrm->getAllEntryIds());
		$this->assertSame([],$xrm->getXrmEntryIds('documents'));
		$this->assertSame([],$xrm->getXrmEntryIds('other',true));
	}

	public function testUnknownEntryTagAndAppChangesAreHarmless(): void {
		$xrm=$this->createFileXrm();
		$id=bin2hex(random_bytes(16));
		$this->assertTrue($xrm->addTag($id,'tag'));
		$this->assertTrue($xrm->removeTag($id,'tag'));
		$this->assertTrue($xrm->addApp($id,'app'));
		$this->assertTrue($xrm->removeApp($id,'app'));
		$this->assertTrue($xrm->addAlloc($id,'other'));
		$this->assertTrue($xrm->removeAlloc($id,'other'));
	}
}

require_once __DIR__ . '/../Cache/CacheUsermanagerFixture.php';
