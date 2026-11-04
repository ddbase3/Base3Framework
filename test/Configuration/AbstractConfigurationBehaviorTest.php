<?php declare(strict_types=1);

namespace Base3\Test\Configuration;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/RecordingConfigurationFixture.php';

final class AbstractConfigurationBehaviorTest extends TestCase {

	public function testConfigurationIsLoadedLazilyAndOnlyOnce(): void {
		$config = new RecordingConfigurationFixture(['base' => ['url' => 'first']]);
		$this->assertSame(0, $config->loadCalls);
		$this->assertFalse($config->isDirty());
		$this->assertSame('first', $config->getValue('base', 'url'));
		$this->assertSame(['url' => 'first'], $config->get('base'));
		$this->assertSame(1, $config->loadCalls);
		$this->assertFalse($config->isDirty());
	}

	public function testRootConfigurationIsNormalizedWithoutNumericGroups(): void {
		$config = new RecordingConfigurationFixture([
			0 => ['excluded' => 1],
			'base' => ['value' => 4],
			'directories' => 'scalar',
		]);
		$this->assertSame(['base' => ['value' => 4], 'directories' => 'scalar'], $config->get());
		$this->assertNull($config->get('missing'));
		$this->assertSame(1, $config->loadCalls);
	}

	public function testLegacySetReplacesOneGroupOrTheWholeConfiguration(): void {
		$config = new RecordingConfigurationFixture(['a' => ['old' => 1], 'b' => ['keep' => 2]]);
		$config->set(['new' => 3], 'a');
		$this->assertSame(['new' => 3], $config->get('a'));
		$this->assertSame(['keep' => 2], $config->get('b'));
		$this->assertTrue($config->isDirty());
		$config->set(['only' => ['value' => 4]]);
		$this->assertSame(['only' => ['value' => 4]], $config->get());
		$this->assertNull($config->get('b'));
	}

	public function testLegacyGetEmptyConfigurationNameReturnsEverything(): void {
		$config = new RecordingConfigurationFixture(['a' => ['key' => null]]);
		$this->assertSame(['a' => ['key' => null]], $config->get(''));
		$this->assertSame(['a' => ['key' => null]], $config->get());
	}

	public function testGetGroupUsesDefaultForMissingAndScalarGroups(): void {
		$config = new RecordingConfigurationFixture(['scalar' => 'raw', 'empty' => []]);
		$this->assertSame(['fallback' => 1], $config->getGroup('missing', ['fallback' => 1]));
		$this->assertSame(['fallback' => 1], $config->getGroup('scalar', ['fallback' => 1]));
		$this->assertSame([], $config->getGroup('empty', ['fallback' => 1]));
	}

	public function testGetValueRespectsPresentNullAndMissingKeys(): void {
		$config = new RecordingConfigurationFixture(['g' => ['null' => null, 'zero' => 0], 'bad' => true]);
		$this->assertNull($config->getValue('g', 'null', 'default'));
		$this->assertSame(0, $config->getValue('g', 'zero', 'default'));
		$this->assertSame('default', $config->getValue('g', 'absent', 'default'));
		$this->assertSame('default', $config->getValue('bad', 'key', 'default'));
		$this->assertSame('default', $config->getValue('missing', 'key', 'default'));
	}

	public function testGetStringConvertsScalarsAndUsesFallbackForContainers(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'string' => 'original', 'int' => 12, 'float' => 1.5, 'yes' => true,
			'no' => false, 'null' => null, 'array' => [], 'object' => new \stdClass(),
		]]);
		$this->assertSame('original', $config->getString('g', 'string'));
		$this->assertSame('12', $config->getString('g', 'int'));
		$this->assertSame('1.5', $config->getString('g', 'float'));
		$this->assertSame('1', $config->getString('g', 'yes'));
		$this->assertSame('', $config->getString('g', 'no'));
		$this->assertSame('fallback', $config->getString('g', 'null', 'fallback'));
		$this->assertSame('fallback', $config->getString('g', 'array', 'fallback'));
		$this->assertSame('fallback', $config->getString('g', 'object', 'fallback'));
		$this->assertSame('fallback', $config->getString('g', 'missing', 'fallback'));
	}

	public function testGetIntConvertsNumericAndBooleanValues(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'int' => -7, 'yes' => true, 'no' => false, 'numeric' => ' 42 ',
			'decimal' => 4.75, 'bad' => '1bad', 'object' => new \stdClass(), 'null' => null,
		]]);
		$this->assertSame(-7, $config->getInt('g', 'int'));
		$this->assertSame(1, $config->getInt('g', 'yes'));
		$this->assertSame(0, $config->getInt('g', 'no'));
		$this->assertSame(42, $config->getInt('g', 'numeric'));
		$this->assertSame(4, $config->getInt('g', 'decimal'));
		$this->assertSame(99, $config->getInt('g', 'bad', 99));
		$this->assertSame(99, $config->getInt('g', 'object', 99));
		$this->assertSame(99, $config->getInt('g', 'null', 99));
		$this->assertSame(99, $config->getInt('g', 'missing', 99));
	}

	public function testGetFloatConvertsIntsAndNumericStrings(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'float' => 3.25, 'int' => -8, 'numeric' => '2.75',
			'bad' => 'yes', 'object' => new \stdClass(), 'null' => null,
		]]);
		$this->assertSame(3.25, $config->getFloat('g', 'float'));
		$this->assertSame(-8.0, $config->getFloat('g', 'int'));
		$this->assertSame(2.75, $config->getFloat('g', 'numeric'));
		$this->assertSame(1.25, $config->getFloat('g', 'bad', 1.25));
		$this->assertSame(1.25, $config->getFloat('g', 'object', 1.25));
		$this->assertSame(1.25, $config->getFloat('g', 'null', 1.25));
		$this->assertSame(1.25, $config->getFloat('g', 'missing', 1.25));
	}

	public function testGetBoolAcceptsAllDocumentedTruthyStrings(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'one' => '1', 'true' => 'TRUE', 'yes' => ' Yes ', 'on' => 'ON',
			'bool' => true, 'int' => -1, 'float' => 0.25,
		]]);
		foreach (['one', 'true', 'yes', 'on', 'bool', 'int', 'float'] as $key) {
			$this->assertTrue($config->getBool('g', $key), $key);
		}
	}

	public function testGetBoolAcceptsAllDocumentedFalsyStrings(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'zero' => '0', 'false' => 'False', 'no' => 'no', 'off' => ' off ',
			'empty' => '', 'bool' => false, 'int' => 0, 'float' => 0.0,
		]]);
		foreach (['zero', 'false', 'no', 'off', 'empty', 'bool', 'int', 'float'] as $key) {
			$this->assertFalse($config->getBool('g', $key, true), $key);
		}
	}

	public function testGetBoolUsesDefaultForUnknownValuesAndNull(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'unknown' => 'maybe', 'array' => [1], 'object' => new \stdClass(), 'null' => null,
		]]);
		foreach (['unknown', 'array', 'object', 'null', 'missing'] as $key) {
			$this->assertTrue($config->getBool('g', $key, true), $key);
			$this->assertFalse($config->getBool('g', $key, false), $key);
		}
	}

	public function testGetArrayAcceptsNativeArraysAndJsonObjectsAndLists(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'array' => ['a' => 1], 'object' => '{"a":2}',
			'list' => ' [1, 2] ', 'emptyObject' => '{}', 'emptyList' => '[]',
		]]);
		$this->assertSame(['a' => 1], $config->getArray('g', 'array'));
		$this->assertSame(['a' => 2], $config->getArray('g', 'object'));
		$this->assertSame([1, 2], $config->getArray('g', 'list'));
		$this->assertSame([], $config->getArray('g', 'emptyObject'));
		$this->assertSame([], $config->getArray('g', 'emptyList'));
	}

	public function testGetArrayFallsBackForInvalidJsonAndNonArrays(): void {
		$config = new RecordingConfigurationFixture(['g' => [
			'badJson' => '{bad}', 'jsonScalar' => '[}', 'plain' => 'hello',
			'empty' => '  ', 'number' => 7, 'null' => null, 'object' => new \stdClass(),
		]]);
		foreach (['badJson', 'jsonScalar', 'plain', 'empty', 'number', 'null', 'object', 'missing'] as $key) {
			$this->assertSame(['fallback'], $config->getArray('g', $key, ['fallback']), $key);
		}
	}

	public function testHasGroupRequiresArrayAndHasValueIncludesNull(): void {
		$config = new RecordingConfigurationFixture(['g' => ['present' => null], 'scalar' => 'data']);
		$this->assertTrue($config->hasGroup('g'));
		$this->assertFalse($config->hasGroup('scalar'));
		$this->assertFalse($config->hasGroup('missing'));
		$this->assertTrue($config->hasValue('g', 'present'));
		$this->assertFalse($config->hasValue('g', 'missing'));
		$this->assertFalse($config->hasValue('scalar', 'key'));
		$this->assertFalse($config->hasValue('missing', 'key'));
	}

	public function testSetValueInitializesMissingOrScalarGroups(): void {
		$config = new RecordingConfigurationFixture(['scalar' => 2]);
		$config->setValue('scalar', 'new', null);
		$config->setValue('missing', 'enabled', false);
		$this->assertSame(['new' => null], $config->getGroup('scalar'));
		$this->assertSame(['enabled' => false], $config->getGroup('missing'));
		$this->assertTrue($config->isDirty());
	}

	public function testSetGroupMergesShallowlyByDefault(): void {
		$config = new RecordingConfigurationFixture(['g' => ['first' => 1, 'nested' => ['old' => 2]]]);
		$config->setGroup('g', ['nested' => ['new' => 3], 'other' => 4]);
		$this->assertSame(['first' => 1, 'nested' => ['new' => 3], 'other' => 4], $config->getGroup('g'));
		$this->assertTrue($config->isDirty());
	}

	public function testSetGroupReplacesWhenMergeDisabledOrExistingValueIsScalar(): void {
		$config = new RecordingConfigurationFixture(['g' => ['original' => true], 'scalar' => 1]);
		$config->setGroup('g', ['replacement' => 2], false);
		$config->setGroup('scalar', ['new' => 3]);
		$config->setGroup('new', ['created' => 4]);
		$this->assertSame(['replacement' => 2], $config->getGroup('g'));
		$this->assertSame(['new' => 3], $config->getGroup('scalar'));
		$this->assertSame(['created' => 4], $config->getGroup('new'));
	}

	public function testSetManySkipsNumericRootKeysAndPreservesScalarGroups(): void {
		$config = new RecordingConfigurationFixture(['g' => ['old' => 1]]);
		$config->setMany([0 => ['ignored' => 7], 'g' => ['new' => 2], 'scalar' => 9]);
		$this->assertSame(['old' => 1, 'new' => 2], $config->getGroup('g'));
		$this->assertSame(9, $config->get('scalar'));
		$this->assertNull($config->get('0'));
		$this->assertTrue($config->isDirty());
	}

	public function testSetManyCanReplaceGroupsAndScalars(): void {
		$config = new RecordingConfigurationFixture(['g' => ['old' => 1], 'scalar' => false]);
		$config->setMany(['g' => ['new' => 2], 'scalar' => ['converted' => 3]], false);
		$this->assertSame(['new' => 2], $config->getGroup('g'));
		$this->assertSame(['converted' => 3], $config->getGroup('scalar'));
	}

	public function testRemovingMissingItemsDoesNotDirtyTheConfiguration(): void {
		$config = new RecordingConfigurationFixture(['g' => ['existing' => 1], 'scalar' => 'x']);
		$config->removeGroup('missing');
		$config->removeValue('g', 'missing');
		$config->removeValue('missing', 'key');
		$config->removeValue('scalar', 'key');
		$this->assertFalse($config->isDirty());
		$this->assertSame(1, $config->loadCalls);
	}

	public function testRemovingNullValueAndWholeScalarGroupMarksDirty(): void {
		$config = new RecordingConfigurationFixture(['g' => ['null' => null, 'keep' => 1], 'scalar' => false]);
		$config->removeValue('g', 'null');
		$config->removeGroup('scalar');
		$this->assertSame(['keep' => 1], $config->getGroup('g'));
		$this->assertNull($config->get('scalar'));
		$this->assertFalse($config->hasValue('g', 'null'));
		$this->assertTrue($config->isDirty());
	}

	public function testCleanSaveIfDirtyAvoidsLoadingOrWriting(): void {
		$config = new RecordingConfigurationFixture(['g' => ['key' => 'value']]);
		$this->assertTrue($config->saveIfDirty());
		$this->assertSame(0, $config->loadCalls);
		$this->assertSame(0, $config->saveCalls);
	}

	public function testTrySaveWritesCurrentSnapshotEvenWhenClean(): void {
		$config = new RecordingConfigurationFixture(['g' => ['key' => 'value']]);
		$this->assertTrue($config->trySave());
		$this->assertSame(1, $config->loadCalls);
		$this->assertSame(1, $config->saveCalls);
		$this->assertSame([['g' => ['key' => 'value']]], $config->savedSnapshots);
		$this->assertFalse($config->isDirty());
	}

	public function testSaveIfDirtyWritesChangedValuesAndResetsDirtyFlag(): void {
		$config = new RecordingConfigurationFixture();
		$config->setValue('g', 'key', 'saved');
		$this->assertTrue($config->isDirty());
		$this->assertTrue($config->saveIfDirty());
		$this->assertSame(['g' => ['key' => 'saved']], $config->storage);
		$this->assertFalse($config->isDirty());
		$this->assertTrue($config->saveIfDirty());
		$this->assertSame(1, $config->saveCalls);
	}

	public function testFailedSaveLeavesDataDirtyAndRetryPersistsIt(): void {
		$config = new RecordingConfigurationFixture(['g' => ['original' => 1]]);
		$config->setValue('g', 'updated', 2);
		$config->saveSucceeds = false;
		$this->assertFalse($config->trySave());
		$this->assertTrue($config->isDirty());
		$this->assertSame(['g' => ['original' => 1]], $config->storage);
		$this->assertSame(2, $config->getValue('g', 'updated'));
		$config->saveSucceeds = true;
		$this->assertTrue($config->saveIfDirty());
		$this->assertFalse($config->isDirty());
		$this->assertSame(['g' => ['original' => 1, 'updated' => 2]], $config->storage);
		$this->assertSame(2, $config->saveCalls);
	}

	public function testLegacySaveDelegatesAndKeepsDirtyWhenStorageRejects(): void {
		$config = new RecordingConfigurationFixture();
		$config->setValue('g', 'key', 1);
		$config->saveSucceeds = false;
		$this->assertNull($config->save());
		$this->assertSame(1, $config->saveCalls);
		$this->assertTrue($config->isDirty());
	}

	public function testReloadAbandonsUnsavedChangesAndReloadsStorage(): void {
		$config = new RecordingConfigurationFixture(['g' => ['old' => 1]]);
		$config->setValue('g', 'local', 2);
		$config->storage = ['g' => ['new' => 3]];
		$config->reload();
		$this->assertSame(['new' => 3], $config->getGroup('g'));
		$this->assertFalse($config->hasValue('g', 'local'));
		$this->assertFalse($config->isDirty());
		$this->assertSame(2, $config->loadCalls);
	}

	public function testPersistValueUsesTheRegularSavePath(): void {
		$config = new RecordingConfigurationFixture(['g' => ['old' => 1]]);
		$this->assertTrue($config->persistValue('g', 'new', 2));
		$this->assertSame(['g' => ['old' => 1, 'new' => 2]], $config->storage);
		$this->assertSame(1, $config->saveCalls);
		$this->assertFalse($config->isDirty());
	}

	public function testFailedPersistValuePreservesDirtyStateForLaterRetry(): void {
		$config = new RecordingConfigurationFixture();
		$config->saveSucceeds = false;
		$this->assertFalse($config->persistValue('g', 'key', 7));
		$this->assertTrue($config->isDirty());
		$this->assertSame([], $config->storage);
		$config->saveSucceeds = true;
		$this->assertTrue($config->saveIfDirty());
		$this->assertSame(['g' => ['key' => 7]], $config->storage);
	}

	public function testSavingReplacedRootPreservesScalarGroupValues(): void {
		$config = new RecordingConfigurationFixture(['old' => ['key' => 1]]);
		$config->set(['scalar' => 0, 'empty' => [], 'null' => null]);
		$this->assertTrue($config->saveIfDirty());
		$this->assertSame(['scalar' => 0, 'empty' => [], 'null' => null], $config->storage);
		$config->reload();
		$this->assertSame(0, $config->get('scalar'));
		$this->assertSame([], $config->getGroup('empty'));
		$this->assertFalse($config->hasGroup('null'));
	}
}
