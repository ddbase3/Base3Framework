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

namespace Base3\Test\Accesscontrol;

use Base3\Core\Accesscontrol\Custom\CustomAccesscontrol;
use Base3\CoreFoundation\Api\IContainer;
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
