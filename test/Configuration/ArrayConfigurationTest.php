<?php declare(strict_types=1);

namespace Base3\Test\Configuration;

use PHPUnit\Framework\TestCase;

final class ArrayConfigurationTest extends TestCase {

	public function testTypedAccessAndJsonArrays(): void {
		$config = new \Base3\Configuration\ArrayConfiguration(['group' => [
			'string' => 17, 'int' => '9', 'float' => '1.25', 'bool' => ' yes ',
			'false' => 'off', 'json' => '{"a":2}', 'broken' => '{not-json}',
			'null' => null, 'object' => new \stdClass()
		]]);
		$this->assertSame('17', $config->getString('group', 'string'));
		$this->assertSame(9, $config->getInt('group', 'int'));
		$this->assertSame(1.25, $config->getFloat('group', 'float'));
		$this->assertTrue($config->getBool('group', 'bool'));
		$this->assertFalse($config->getBool('group', 'false', true));
		$this->assertSame(['a' => 2], $config->getArray('group', 'json'));
		$this->assertSame(['default'], $config->getArray('group', 'broken', ['default']));
		$this->assertSame('fallback', $config->getString('group', 'object', 'fallback'));
		$this->assertSame(3, $config->getInt('group', 'null', 3));
		$this->assertSame(2.5, $config->getFloat('group', 'object', 2.5));
		$this->assertFalse($config->hasGroup('missing'));
		$this->assertTrue($config->hasValue('group', 'null'));
	}

	public function testWritesMergesReplacesAndPersistsValues(): void {
		$config = new \Base3\Configuration\ArrayConfiguration(['one' => ['a' => 1, 'b' => 2]]);
		$this->assertFalse($config->isDirty());
		$this->assertTrue($config->saveIfDirty());
		$config->setGroup('one', ['b' => 4, 'c' => 5]);
		$this->assertSame(['a' => 1, 'b' => 4, 'c' => 5], $config->getGroup('one'));
		$config->setGroup('one', ['z' => 9], false);
		$this->assertSame(['z' => 9], $config->getGroup('one'));
		$config->setMany(['two' => ['a' => 3], 'raw' => 42, 7 => ['ignored' => true]]);
		$this->assertSame(42, $config->get('raw'));
		$this->assertNull($config->get(7));
		$this->assertTrue($config->persistValue('two', 'b', 5));
		$this->assertFalse($config->isDirty());
		$this->assertSame(['a' => 3, 'b' => 5], $config->getGroup('two'));
		$config->removeValue('two', 'a');
		$config->removeGroup('one');
		$this->assertFalse($config->hasGroup('one'));
		$this->assertSame(['b' => 5], $config->getGroup('two'));
		$this->assertTrue($config->isDirty());
		$config->reload();
		$this->assertFalse($config->isDirty());
	}

	public function testLegacyGetAndSetResetRoot(): void {
		$config = new \Base3\Configuration\ArrayConfiguration(['a' => 1]);
		$this->assertSame(['a' => 1], $config->get());
		$config->set('hello', 'b');
		$this->assertSame('hello', $config->get('b'));
		$config->set('invalid');
		$this->assertSame([], $config->get());
	}

}
