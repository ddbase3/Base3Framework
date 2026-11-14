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

namespace Base3\Test\Configuration\ConfigFile;

use Base3\Core\Configuration\ConfigFile\ConfigFile;
use PHPUnit\Framework\TestCase;

final class ConfigFileEdgeCasesTest extends TestCase {

	private function inTempConfiguration(callable $run): void {
		$directory = sys_get_temp_dir() . '/base3-config-edges-' . bin2hex(random_bytes(8));
		if (!mkdir($directory, 0700)) self::fail('Unable to create temporary configuration directory');
		if (!defined('Base3\\Core\\Configuration\\ConfigFile\\DIR_CNF')) {
			define('Base3\\Core\\Configuration\\ConfigFile\\DIR_CNF', $directory . DIRECTORY_SEPARATOR);
		}
		$previous = getenv('CONFIG_FILE');
		putenv('CONFIG_FILE=' . $directory . '/config.ini');
		try {
			$run($directory, new ConfigFile());
		} finally {
			if ($previous === false) putenv('CONFIG_FILE');
			else putenv('CONFIG_FILE=' . $previous);
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ($files as $file) {
				if ($file->isDir()) rmdir($file->getPathname());
				else unlink($file->getPathname());
			}
			rmdir($directory);
		}
	}

	public function testRecursiveIncludesMergeSectionsAndRemoveIncludeGroup(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			file_put_contents($dir . '/second.ini', "[shared]\nlevel = \"second\"\n[extra]\nfound = \"yes\"\n");
			file_put_contents($dir . '/first.ini', "[include]\nfiles[] = \"second.ini\"\n[shared]\nfirst = \"one\"\n");
			file_put_contents($dir . '/config.ini', "[directories]\ndata = \"$dir\"\n[include]\nfiles[] = \"first.ini\"\n[shared]\nlevel = \"root\"\n");
			$this->assertSame('second', $config->getValue('shared', 'level'));
			$this->assertSame('one', $config->getValue('shared', 'first'));
			$this->assertSame('yes', $config->getValue('extra', 'found'));
			$this->assertFalse($config->hasGroup('include'));
		});
	}

	public function testIncludeResolvesRelativeToMainFileWhenNoDataDirectoryExists(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			file_put_contents($dir . '/child.ini', "[child]\nvalue = \"relative\"\n");
			file_put_contents($dir . '/config.ini', "[include]\nfiles[] = \"child.ini\"\n[main]\nvalue = \"root\"\n");
			$this->assertSame('relative', $config->getValue('child', 'value'));
			$this->assertSame('root', $config->getValue('main', 'value'));
			$this->assertFalse($config->hasGroup('include'));
		});
	}

	public function testMissingIncludePreservesOtherConfigurationValues(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			file_put_contents($dir . '/config.ini', "[include]\nfiles[] = \"missing.ini\"\n[app]\nactive = \"yes\"\n");
			$this->assertSame('yes', $config->getValue('app', 'active'));
			$this->assertFalse($config->hasGroup('include'));
			$this->assertFalse($config->isDirty());
		});
	}

	public function testSavingEscapesStringsAndSupportsNumericAndUppercaseValues(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			$config->setGroup('app', [
				'number' => 12,
				'decimal' => 1.5,
				'uppercase' => 'UPPERCASE',
				'quoted' => 'some "quote" here',
			]);
			$this->assertTrue($config->saveIfDirty());
			$serialized = file_get_contents($dir . '/config.ini');
			$this->assertStringContainsString('number = 12', $serialized);
			$this->assertStringContainsString('decimal = 1.5', $serialized);
			$this->assertStringContainsString('uppercase = UPPERCASE', $serialized);
			$this->assertStringContainsString('quoted = "some \\"quote\\" here"', $serialized);
			$this->assertFalse($config->isDirty());
		});
	}

	public function testWritingIndexedAndAssociativeValuesPreservesIniSections(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			$config->setGroup('app', ['items' => ['one', 'two'], 'labels' => ['first' => 'a', 'second' => 'b']]);
			$this->assertTrue($config->trySave());
			$ini = file_get_contents($dir . '/config.ini');
			$this->assertStringContainsString('items[] = "one"', $ini);
			$this->assertStringContainsString('items[] = "two"', $ini);
			$this->assertStringContainsString('labels[first] = "a"', $ini);
			$this->assertStringContainsString('labels[second] = "b"', $ini);
		});
	}

	public function testRootScalarAndEmptyGroupAreWrittenWithoutDroppingSections(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			$config->set(['scalar' => 'hello', 'empty' => [], 'group' => ['answer' => 42]]);
			$this->assertTrue($config->saveIfDirty());
			$serialized = file_get_contents($dir . '/config.ini');
			$this->assertStringContainsString('scalar = "hello"', $serialized);
			$this->assertStringContainsString('[empty]', $serialized);
			$this->assertStringContainsString('[group]', $serialized);
			$this->assertStringContainsString('answer = 42', $serialized);
		});
	}

	public function testPersistValueSavesImmediatelyAndReloadReadsWrittenValues(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			$this->assertTrue($config->persistValue('runtime', 'count', 7));
			$this->assertFalse($config->isDirty());
			$this->assertStringContainsString('count = 7', file_get_contents($dir . '/config.ini'));
			$config->setValue('runtime', 'temp', 'not saved');
			$config->reload();
			$this->assertSame('7', $config->getValue('runtime', 'count'));
			$this->assertFalse($config->hasValue('runtime', 'temp'));
			$this->assertFalse($config->isDirty());
		});
	}

	public function testReloadReadsExternalChangesAndAbandonsDirtyValues(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			file_put_contents($dir . '/config.ini', "[g]\noriginal = \"first\"\n");
			$this->assertSame('first', $config->getString('g', 'original'));
			$config->setValue('g', 'uncommitted', 2);
			file_put_contents($dir . '/config.ini', "[g]\noriginal = \"second\"\n");
			$config->reload();
			$this->assertSame('second', $config->getString('g', 'original'));
			$this->assertFalse($config->hasValue('g', 'uncommitted'));
			$this->assertFalse($config->isDirty());
		});
	}

	public function testCleanSaveDoesNotWriteMissingConfigurationFile(): void {
		$this->inTempConfiguration(function (string $dir, ConfigFile $config): void {
			$this->assertTrue($config->saveIfDirty());
			$this->assertFalse(file_exists($dir . '/config.ini'));
			$this->assertSame([], $config->get());
		});
	}
}
