<?php declare(strict_types=1);

namespace Base3\Test\Configuration;

use Base3\Configuration\ArrayConfiguration;
use PHPUnit\Framework\TestCase;

final class ArrayConfigurationEdgeCasesTest extends TestCase {

	public function testNullRootSelectorReturnsEntireConfiguration(): void {
		$config = new ArrayConfiguration(['base' => ['key' => 1]]);
		$this->assertSame(['base' => ['key' => 1]], $config->get(null));
		$config->set(['new' => ['key' => 2]], null);
		$this->assertSame(['new' => ['key' => 2]], $config->get());
		$this->assertTrue($config->isDirty());
	}

	public function testTypedGettersHandleBooleanAndNumericEdgeCases(): void {
		$config = new ArrayConfiguration(['g' => [
			'on' => ' TRUE ', 'off' => ' OFF ', 'floatZero' => 0.0,
			'floatNonzero' => -0.25, 'intZero' => 0, 'intNonzero' => 5,
			'bad' => 'yes-ish', 'numericFloat' => '2.75', 'null' => null,
		]]);
		$this->assertTrue($config->getBool('g', 'on'));
		$this->assertFalse($config->getBool('g', 'off', true));
		$this->assertFalse($config->getBool('g', 'floatZero', true));
		$this->assertTrue($config->getBool('g', 'floatNonzero'));
		$this->assertFalse($config->getBool('g', 'intZero', true));
		$this->assertTrue($config->getBool('g', 'intNonzero'));
		$this->assertTrue($config->getBool('g', 'bad', true));
		$this->assertSame(2.75, $config->getFloat('g', 'numericFloat'));
		$this->assertSame(4.25, $config->getFloat('g', 'null', 4.25));
	}

	public function testJsonArrayGetterFallsBackForScalarsAndInvalidInput(): void {
		$config = new ArrayConfiguration(['g' => [
			'array' => ['keep' => 1], 'json' => '[1,2]', 'bad' => '{bad',
			'plain' => 'hello', 'object' => new \stdClass(), 'null' => null,
		]]);
		$this->assertSame(['keep' => 1], $config->getArray('g', 'array'));
		$this->assertSame([1, 2], $config->getArray('g', 'json'));
		foreach (['bad', 'plain', 'object', 'null', 'missing'] as $key) {
			$this->assertSame(['fallback'], $config->getArray('g', $key, ['fallback']));
		}
	}

	public function testHasValueTreatsPresentNullAsExisting(): void {
		$config = new ArrayConfiguration(['g' => ['null' => null], 'scalar' => true]);
		$this->assertTrue($config->hasGroup('g'));
		$this->assertFalse($config->hasGroup('scalar'));
		$this->assertTrue($config->hasValue('g', 'null'));
		$this->assertFalse($config->hasValue('g', 'missing'));
		$this->assertFalse($config->hasValue('scalar', 'key'));
	}

	public function testSetManyDoesNotMarkDirtyWhenOnlyNumericGroupNamesAreProvided(): void {
		$config = new ArrayConfiguration();
		$config->setMany([0 => ['ignored' => 2], 1 => 'discarded']);
		$this->assertSame([], $config->get());
		$this->assertFalse($config->isDirty());
		$config->setMany(['group' => ['value' => 1]], false);
		$this->assertTrue($config->isDirty());
	}

	public function testRemovingMissingKeysAndGroupsDoesNotMarkDirty(): void {
		$config = new ArrayConfiguration(['g' => ['keep' => 1], 'scalar' => 2]);
		$config->removeGroup('missing');
		$config->removeValue('g', 'missing');
		$config->removeValue('scalar', 'key');
		$this->assertFalse($config->isDirty());
		$config->removeValue('g', 'keep');
		$this->assertTrue($config->isDirty());
		$this->assertSame([], $config->getGroup('g'));
	}

	public function testLegacySaveClearsDirtyWithoutChangingCurrentValues(): void {
		$config = new ArrayConfiguration(['g' => ['key' => 'first']]);
		$config->setValue('g', 'key', 'second');
		$this->assertNull($config->save());
		$this->assertSame('second', $config->getValue('g', 'key'));
		$this->assertFalse($config->isDirty());
		$this->assertTrue($config->saveIfDirty());
		}

	public function testSettingScalarRootWithoutGroupResetsToEmptyConfiguration(): void {
		$config = new ArrayConfiguration(['g' => ['key' => 1]]);
		$config->set(false);
		$this->assertSame([], $config->get());
		$this->assertTrue($config->isDirty());
		$config->reload();
		$this->assertFalse($config->isDirty());
		$this->assertSame([], $config->get());
	}
}
