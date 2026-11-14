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

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Base3\Bootstrap\Autoloader;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AutoloaderSmokeTest extends TestCase {

	public static function setUpBeforeClass(): void {
		$frameworkRoot = dirname(__DIR__, 2);
		$pluginRoot = is_dir($frameworkRoot . '/plugin') ? $frameworkRoot . '/plugin' : dirname($frameworkRoot);
		foreach ([
			'DIR_TEST' => $frameworkRoot . '/test/',
			'DIR_PLUGIN' => rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
		] as $name => $value) {
			if (!defined($name)) define($name, $value);
		}
		Autoloader::register();

	}

	public function testAutoloaderLoadsDummyClass(): void {
		$dummy = new \Base3\Test\Dummy\DummyClass();
		$this->assertSame('Hello from DummyClass!', $dummy->sayHello());
	}
}
