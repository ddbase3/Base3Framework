<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol;

use Base3\Accesscontrol\Custom\CustomAccesscontrol;
use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Test\Support\StaticClassMapStub;
use PHPUnit\Framework\TestCase;

final class CustomAccesscontrolEdgeCoverageTest extends TestCase {

	private function control(array $authentications): CustomAccesscontrol {
		$container = new ServiceLocator();
		$classmap = new StaticClassMapStub();
		foreach ($authentications as $name => $auth) $classmap->register((string)$name, $auth);
		$container->set('classmap', $classmap, IContainer::SHARED);
		ServiceLocator::useInstance($container);
		return new CustomAccesscontrol();
	}

	public function testEmptyClassmapAuthenticatesWithoutIdentity(): void {
		$previous = ServiceLocator::getInstance();
		try {
			$control = $this->control([]);
			$this->assertSame(['depending_services' => 'Ok'], $control->checkDependencies());
			$control->authenticate();
			$this->assertNull($control->getUserId());
			$control->authenticate();
		} finally { ServiceLocator::useInstance($previous); }
	}

	public function testLastIdentifiedUserIsKeptAcrossAllMethods(): void {
		$previous = ServiceLocator::getInstance();
		try {
			$a = new RecordingAuthentication('member');
			$b = new RecordingAuthentication(null);
			$c = new RecordingAuthentication('admin');
			$control = $this->control(['a' => $a, 'b' => $b, 'c' => $c]);
			$control->authenticate();
			$this->assertSame('admin', $control->getUserId());
			foreach ([$a, $b, $c] as $auth) {
				$this->assertSame(['logout', 'login', 'keep:admin', 'finish:admin'], $auth->lifecycle);
			}
		} finally { ServiceLocator::useInstance($previous); }
	}

	public function testAnonymousAuthenticationSkipsKeepButCallsFinish(): void {
		$previous = ServiceLocator::getInstance();
		try {
			$a = new RecordingAuthentication(null);
			$control = $this->control(['anon' => $a]);
			$control->authenticate();
			$this->assertSame(['logout', 'login', 'finish:null'], $a->lifecycle);
		} finally { ServiceLocator::useInstance($previous); }
	}

	public function testMissingClassmapIsReportedByDependencies(): void {
		$previous = ServiceLocator::getInstance();
		try {
			ServiceLocator::useInstance(new ServiceLocator());
			$control = new CustomAccesscontrol();
			$this->assertSame(['depending_services' => 'Fail'], $control->checkDependencies());
			$this->assertNull($control->getUserId());
		} finally { ServiceLocator::useInstance($previous); }
	}
}

require_once __DIR__ . '/RecordingAuthentication.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
