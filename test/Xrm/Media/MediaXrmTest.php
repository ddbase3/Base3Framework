<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Media;

use Base3\Core\ServiceLocator;
use Base3\Test\Xrm\Cache\CacheUsermanagerFixture;
use Base3\Xrm\Media\MediaXrm;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MediaXrmTest extends TestCase {

	private string $userFilesDir;

	protected function setUp(): void {
		$this->userFilesDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR . 'base3_mediaxrm_test_' . bin2hex(random_bytes(8)) . DIRECTORY_SEPARATOR;
		if (!mkdir($this->userFilesDir, 0700, true) && !is_dir($this->userFilesDir)) {
			self::fail('Could not create an isolated MediaXrm test directory');
		}
		define('Base3\\Xrm\\Media\\DIR_USERFILES', $this->userFilesDir);
	}

	protected function tearDown(): void {
		if (is_dir($this->userFilesDir)) rmdir($this->userFilesDir);
	}

	private function createMediaXrm(): MediaXrm {
		$previous=ServiceLocator::getInstance();
		$container=new ServiceLocator();
		$container->set('usermanager',new CacheUsermanagerFixture(),ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new MediaXrm('media'); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testUnknownMediaEntryIsAbsent(): void {
		$xrm=$this->createMediaXrm();
		$id=bin2hex(random_bytes(16));
		$this->assertSame('media',$xrm->getXrmName());
		$this->assertNull($xrm->getEntry($id));
		$this->assertSame([],$xrm->getEntries([$id]));
		$this->assertFalse($xrm->delEntry($id));
		$this->assertSame([],$xrm->checkDependencies());
	}

	public function testNonAdminCannotEnumerateMedia(): void {
		$xrm=$this->createMediaXrm();
		$this->assertSame([],$xrm->getAllEntryIds());
		$this->assertSame([],$xrm->getXrmEntryIds('media'));
		$this->assertSame([],$xrm->getXrmEntryIds('other',true));
	}

	public function testUnknownMediaRelationsDoNotTriggerWrites(): void {
		$xrm=$this->createMediaXrm();
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
