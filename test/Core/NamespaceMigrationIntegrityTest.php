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

use Base3\Core\ModuleRegistry;
use PHPUnit\Framework\TestCase;

/** Read-only regression checks for the agreed namespace and dependency direction. */
final class NamespaceMigrationIntegrityTest extends TestCase {

	private const MODULES = [
		'AssistantFoundation', 'AssistantRuntime', 'Base3Backend', 'Base3ChatWebsite',
		'Base3Template', 'Base3Tools', 'Chatbot', 'ChatbotExtensions', 'ClientStack',
		'Core', 'CoreFoundation', 'CredentialFoundation', 'DatabaseWorkbench',
		'DataHawk', 'FileBridge', 'KeyHarbor', 'MediaFoundation', 'MessageHub',
		'MessagingFoundation', 'MissionBay', 'MissionBayReporting', 'MissionBayXrm',
		'ModuledPage', 'PulseCore', 'ResourceFoundation', 'UiFoundation', 'Vizion'
	];

	public function testAllModuleManifestsUseCommonBase3Root(): void {
		$root = dirname(__DIR__, 2);
		$pluginRoot = $root . '/plugin';
		$installed = [];
		foreach (scandir($pluginRoot) ?: [] as $entry) {
			if ($entry === '.' || $entry === '..' || $entry[0] === '.') continue;
			$dir = $pluginRoot . '/' . $entry;
			if (is_dir($dir)) $installed[] = $entry;
		}
		sort($installed);
		$expected = self::MODULES;
		sort($expected);
		foreach ($expected as $name) {
			$this->assertContains($name, $installed, 'A tracked module directory is missing: ' . $name);
		}
		foreach ($installed as $name) {
			if (is_dir($pluginRoot . '/' . $name . '/src')) {
				$this->assertFileExists($pluginRoot . '/' . $name . '/base3.json', 'An additional module is missing its manifest: ' . $name);
			}
		}

		foreach (self::MODULES as $name) {
			$file = $pluginRoot . '/' . $name . '/base3.json';
			$this->assertFileExists($file, $name);
			$data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
			$this->assertSame(1, $data['manifestVersion'] ?? null, $file);
			$this->assertSame($name, $data['name'] ?? null, $file);
			$this->assertSame('Base3\\' . $name, $data['namespace'] ?? null, $file);
			$this->assertNotEmpty($data['version'] ?? null, $file);
			$this->assertSame(0, preg_match('/^ +\S/m', (string) file_get_contents($file)), 'Manifest indentation must use tabs: ' . $file);
			$dependencies = $data['dependencies'] ?? [];
			$this->assertIsArray($dependencies, $file);
			$this->assertArrayNotHasKey('Base3Framework', $dependencies, $file);
			foreach (array_keys($dependencies) as $dependency) {
				$this->assertContains($dependency, self::MODULES, 'Unknown dependency in ' . $file);
			}
		}
	}

	public function testCoreDependsOnFoundationButNotConversely(): void {
		$root = dirname(__DIR__, 2);
		$core = json_decode((string) file_get_contents($root . '/plugin/Core/base3.json'), true, 512, JSON_THROW_ON_ERROR);
		$foundation = json_decode((string) file_get_contents($root . '/plugin/CoreFoundation/base3.json'), true, 512, JSON_THROW_ON_ERROR);
		$this->assertArrayHasKey('CoreFoundation', $core['dependencies']);
		$this->assertArrayNotHasKey('Core', $foundation['dependencies'] ?? []);
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/plugin/CoreFoundation/src')) as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'php') continue;
			$this->assertStringNotContainsString('Base3\\Core\\', (string) file_get_contents($file->getPathname()), $file->getPathname());
		}
	}

	public function testSourceIsOwnedByModules(): void {
		$root = dirname(__DIR__, 2);
		$this->assertFileDoesNotExist($root . '/src/Core/Autoloader.php');
		$this->assertFileExists($root . '/bootstrap/Autoloader.php');
		$this->assertFileExists($root . '/plugin/Core/src/Knowledge/Source/Data/country_en.json');
	}
	public function testPhpunitDiscoveryDoesNotExecuteStandaloneSmokePages(): void {
		$root = dirname(__DIR__, 2);
		foreach ([
			'plugin/Base3Backend/test/BackendPluginCompositionTest.php' => 'plugin/Base3Backend/test/BackendPluginCompositionSmoke.php',
			'test/Util/Chronos/ChronosTest.php' => 'test/Util/Chronos/ChronosExamplePage.php',
			'test/Util/MailParser/MailParserTest.php' => 'test/Util/MailParser/MailParserExamplePage.php'
		] as $old => $replacement) {
			$this->assertFileDoesNotExist($root . '/' . $old, 'PHPUnit must not collect a standalone example as a test: ' . $old);
			$this->assertFileExists($root . '/' . $replacement);
		}
	}

}
