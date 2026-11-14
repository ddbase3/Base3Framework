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

namespace Base3\Test\Configuration\Database;

use Base3\Core\Configuration\Database\DatabaseConfiguration;
use Base3\Test\Support\RecordingDatabaseStub;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';

final class DatabaseConfigurationEdgeCasesTest extends TestCase {

	private function assertRuntimeFailure(string $expectedText, callable $operation): void {
		try {
			$operation();
			self::fail('Expected RuntimeException containing: ' . $expectedText);
		} catch (\RuntimeException $e) {
			$this->assertStringContainsString($expectedText, $e->getMessage());
		}
	}

	public function testMissingRowsReturnEmptyConfigurationWithoutRepeatedTableCreation(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$this->assertSame([], $config->get());
		$this->assertSame([], $config->getGroup('missing'));
		$this->assertSame(1, $database->connectCalls);
		$this->assertCount(1, $database->nonQueries);
		$this->assertCount(1, $database->multiQueries);
		$this->assertFalse($config->isDirty());
	}

	public function testMissingRowTypeAndUnknownTypeAreReadAsStrings(): void {
		$database = new RecordingDatabaseStub();
		$database->multiRows = [
			['group' => 'g', 'name' => 'unknown', 'type' => '  CUSTOM ', 'value' => 'original'],
			['group' => 'g', 'name' => 'missingType', 'value' => 'untagged'],
			['group' => 'g', 'name' => 'missingValue', 'type' => 'string'],
		];
		$config = new DatabaseConfiguration($database);
		$this->assertSame(['unknown' => 'original', 'missingType' => 'untagged', 'missingValue' => ''], $config->getGroup('g'));
	}

	public function testBooleanRowsRecognizeAllTruthyLiterals(): void {
		$database = new RecordingDatabaseStub();
		foreach (['1', 'true', 'yes', 'on', ' YES ', 'TRUE', 'On'] as $index => $value) {
			$database->multiRows[] = ['group' => 'g', 'name' => 'b' . $index, 'type' => 'bool', 'value' => $value];
		}
		$config = new DatabaseConfiguration($database);
		foreach (range(0, 6) as $index) $this->assertTrue($config->getValue('g', 'b' . $index));
	}

	public function testBooleanRowsRejectUnknownLiterals(): void {
		$database = new RecordingDatabaseStub();
		foreach (['0', 'false', 'no', 'off', 'garbage', ''] as $index => $value) {
			$database->multiRows[] = ['group' => 'g', 'name' => 'b' . $index, 'type' => 'bool', 'value' => $value];
		}
		$config = new DatabaseConfiguration($database);
		foreach (range(0, 5) as $index) $this->assertFalse($config->getValue('g', 'b' . $index));
	}

	public function testRowThatIsNotAnArrayIsRejected(): void {
		$database = new RecordingDatabaseStub();
		$database->multiRows = ['invalid'];
		$this->assertRuntimeFailure('Invalid database row', function () use ($database): void {
			(new DatabaseConfiguration($database))->get();
		});
	}

	public function testRowWithoutRequiredNameIsRejected(): void {
		$database = new RecordingDatabaseStub();
		$database->multiRows = [['group' => 'g', 'value' => 'v']];
		$this->assertRuntimeFailure('Invalid database row', function () use ($database): void {
			(new DatabaseConfiguration($database))->get();
		});
	}

	public function testEmptyGroupOrNameInRowsIsRejected(): void {
		foreach ([['group' => '', 'name' => 'n'], ['group' => 'g', 'name' => '']] as $row) {
			$database = new RecordingDatabaseStub();
			$database->multiRows = [$row + ['value' => 'x']];
			$this->assertRuntimeFailure('Invalid empty group or name', function () use ($database): void {
				(new DatabaseConfiguration($database))->get();
			});
		}
	}

	public function testReloadReplacesCachedDataWithLatestDatabaseRows(): void {
		$database = new RecordingDatabaseStub();
		$database->multiRows = [['group' => 'g', 'name' => 'key', 'type' => 'string', 'value' => 'before']];
		$config = new DatabaseConfiguration($database);
		$this->assertSame('before', $config->getValue('g', 'key'));
		$config->setValue('g', 'uncommitted', 3);
		$database->multiRows = [['group' => 'g', 'name' => 'key', 'type' => 'string', 'value' => 'after']];
		$config->reload();
		$this->assertSame('after', $config->getValue('g', 'key'));
		$this->assertFalse($config->hasValue('g', 'uncommitted'));
		$this->assertFalse($config->isDirty());
		$this->assertSame(1, $database->connectCalls);
		$this->assertCount(2, $database->multiQueries);
	}

	public function testPersistValueRejectsEmptyNameWithoutWritingAnInsert(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$this->assertRuntimeFailure('name must not be empty', function () use ($config): void {
			$config->persistValue('g', '', 'value');
		});
		$this->assertSame(1, count($database->nonQueries));
		$this->assertTrue($config->isDirty());
	}

	public function testSaveRejectsEmptyGroupWithoutWritingRows(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$config->setValue('', 'key', 1);
		$this->assertRuntimeFailure('group must not be empty', function () use ($config): void {
			$config->trySave();
		});
		$this->assertTrue($config->isDirty());
		$this->assertCount(1, $database->nonQueries);
	}

	public function testSaveRejectsEmptyNameWithoutWritingAnInsert(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$config->setValue('g', '', 1);
		$this->assertRuntimeFailure('name must not be empty', function () use ($config): void {
			$config->trySave();
		});
		$this->assertTrue($config->isDirty());
		$this->assertCount(1, $database->nonQueries);
	}

	public function testSaveSkipsNumericRootAndNonArrayGroupValues(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$config->set([0 => ['excluded' => true], 'raw' => 'value', 'g' => ['key' => 5]]);
		$this->assertTrue($config->trySave());
		$this->assertCount(2, $database->nonQueries);
		$this->assertStringContainsString('INSERT INTO', $database->nonQueries[1]);
		$this->assertStringContainsString("'key'", $database->nonQueries[1]);
		$this->assertFalse($config->isDirty());
	}

	public function testSaveDeletesOnlyObsoleteKeysUsingDatabaseEscaping(): void {
		$database = new RecordingDatabaseStub();
		$database->multiRows = [['group' => "g'1", 'name' => "key'2", 'type' => 'string', 'value' => 'value']];
		$config = new DatabaseConfiguration($database);
		$this->assertSame('value', $config->getValue("g'1", "key'2"));
		$config->removeGroup("g'1");
		$this->assertTrue($config->saveIfDirty());
		$this->assertCount(2, $database->nonQueries);
		$this->assertStringContainsString("g\\'1", $database->nonQueries[1]);
		$this->assertStringContainsString("key\\'2", $database->nonQueries[1]);
		$this->assertStringContainsString('DELETE FROM', $database->nonQueries[1]);
	}

	public function testSaveRejectsMalformedExistingKeysDuringSynchronization(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$this->assertSame([], $config->get());
		$database->multiRows = [['group' => 'g']];
		$config->setValue('g', 'key', 'value');
		$this->assertRuntimeFailure('Invalid database row while synchronizing', function () use ($config): void {
			$config->trySave();
		});
		$this->assertTrue($config->isDirty());
	}

	public function testInsertErrorAfterSuccessfulLoadDoesNotClearDirtyState(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$config->get();
		$config->setValue('g', 'key', 1);
		$database->error = true;
		$database->errorCode = 17;
		$database->errorText = 'write denied';
		$this->assertRuntimeFailure('Failed to persist configuration value "g/key". [17] write denied', function () use ($config): void {
			$config->trySave();
		});
		$this->assertTrue($config->isDirty());
	}

	public function testDatabaseErrorWithEmptyDetailsDoesNotInventErrorText(): void {
		$database = new RecordingDatabaseStub();
		$database->error = true;
		$database->errorCode = 2;
		$database->errorText = '';
		$this->assertRuntimeFailure('Failed to connect to database.', function () use ($database): void {
			(new DatabaseConfiguration($database))->get();
		});
	}

	public function testPersistingRepeatedValuesCreatesTheTableOnlyOnce(): void {
		$database = new RecordingDatabaseStub();
		$config = new DatabaseConfiguration($database);
		$this->assertTrue($config->persistValue('g', 'first', 1));
		$this->assertTrue($config->persistValue('g', 'second', 2));
		$this->assertSame(1, $database->connectCalls);
		$this->assertCount(3, $database->nonQueries);
		$this->assertSame(['first' => 1, 'second' => 2], $config->getGroup('g'));
	}

	public function testArrayRowsDecodeWhitespaceAndNestedContainers(): void {
		$database = new RecordingDatabaseStub();
		$database->multiRows = [
			['group' => 'g', 'name' => 'nested', 'type' => ' ARRAY ', 'value' => ' {"meta":{"enabled":true},"items":[1,2]} '],
			['group' => 'g', 'name' => 'empty', 'type' => 'array', 'value' => '  '],
		];
		$config = new DatabaseConfiguration($database);
		$this->assertSame(['meta' => ['enabled' => true], 'items' => [1, 2]], $config->getArray('g', 'nested'));
		$this->assertSame([], $config->getArray('g', 'empty'));
	}
}
