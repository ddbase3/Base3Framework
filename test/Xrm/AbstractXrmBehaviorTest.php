<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use Base3\Core\ServiceLocator;
use Base3\Test\Xrm\Cache\CacheUsermanagerFixture;
use Base3\Xrm\Cache\CacheXrm;
use Base3\Xrm\XrmFilter;
use PHPUnit\Framework\TestCase;

final class AbstractXrmBehaviorTest extends TestCase {

	private function xrm(array $user = ['id'=>'alice','role'=>'reader'], array $groups = []): CacheXrm {
		$original = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$container->set('usermanager', new CacheUsermanagerFixture($user,$groups), ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { $xrm = new CacheXrm('memory'); }
		finally { ServiceLocator::useInstance($original); }
		return $xrm;
	}

	public function testPseudoEntriesAndAdminsGetWriteAccess(): void {
		$xrm = $this->xrm();
		$this->assertSame('write', $xrm->getAccess(['id'=>'xxtaexample', 'access'=>[]]));
		$admin = $this->xrm(['id'=>'root','role'=>'admin']);
		$this->assertSame('write',$admin->getAccess(['id'=>'a','access'=>[]]));
	}

	public function testDefaultAndOwnedUserAccessIsEvaluated(): void {
		$xrm = $this->xrm();
		$entry = ['id'=>'a','access'=>[['usergroup'=>'user','id'=>'default','mode'=>'read']]];
		$this->assertSame('read',$xrm->getAccess($entry));
		$entry['access'][] = ['usergroup'=>'user','id'=>'alice','mode'=>'owner'];
		$this->assertSame('write',$xrm->getAccess($entry));
	}

	public function testUnrelatedGrantsDoNotAuthorizeAccess(): void {
		$xrm = $this->xrm();
		$this->assertSame('none',$xrm->getAccess(['id'=>'a','access'=>[['usergroup'=>'user','id'=>'bob','mode'=>'write']]]));
		$this->assertSame('none',$xrm->getAccess(['id'=>'a','access'=>[['usergroup'=>'group','id'=>'unknown','mode'=>'write']]]));
	}

	public function testGroupGrantsAndDefaultGroupModeAreApplied(): void {
		$xrm = $this->xrm(['id'=>'alice','role'=>'reader'],[['id'=>'team']]);
		$this->assertSame('read',$xrm->getAccess(['id'=>'a','access'=>[['usergroup'=>'group','id'=>'team','mode'=>'read']]]));
		$this->assertSame('write',$xrm->getAccess(['id'=>'a','access'=>[['usergroup'=>'group','id'=>'default','mode'=>'write']]]));
	}

	public function testConnectionOperationsRequireBothDirectionsToSucceed(): void {
		$xrm = $this->xrm();
		$this->assertTrue($xrm->connect('a','b'));
		$this->assertTrue($xrm->disconnect('a','b'));
		$this->assertNull($xrm->getUserEntryId());
	}
}

require_once __DIR__ . '/Cache/CacheUsermanagerFixture.php';
