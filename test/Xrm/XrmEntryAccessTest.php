<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class XrmEntryAccessTest extends TestCase {

	public function testDecodesJsonObjectAndExistingInstance(): void {
		$existing = new \Base3\Xrm\XrmEntryAccess();
		$this->assertSame($existing, \Base3\Xrm\XrmEntryAccess::unserialize($existing));
		$access = \Base3\Xrm\XrmEntryAccess::unserialize('{"mode":"read","usergroup":"group","id":5}');
		$this->assertSame('read', $access->mode);
		$this->assertSame('group', $access->usergroup);
		$this->assertSame(5, $access->id);
		$object = \Base3\Xrm\XrmEntryAccess::unserialize((object)['mode' => 'write', 'usergroup' => 'user', 'id' => 'abc']);
		$this->assertSame('abc', $object->id);
	}

}
