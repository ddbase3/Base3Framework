<?php declare(strict_types=1);

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class XrmEntryTest extends TestCase {

	public function testDefaultCollectionsAndTagLifecycle(): void {
		$entry = new \Base3\Xrm\XrmEntry();
		$this->assertSame([], $entry->alloc);
		$this->assertSame([], $entry->access);
		$this->assertSame([], $entry->log);
		$this->assertSame(0, $entry->archive);
		$entry->addTag('security');
		$entry->addApp('portal');
		$this->assertTrue($entry->hasTag('security'));
		$this->assertTrue($entry->hasApp('portal'));
		$this->assertSame(['security'], $entry->getTags());
		$this->assertSame(['portal'], $entry->getApps());
		$this->assertFalse($entry->isConnected('xxtasecurity'));
		$entry->alloc[] = 'edge-1';
		$this->assertTrue($entry->isConnected('edge-1'));
		$entry->removeTag('security');
		$entry->removeApp('portal');
		$this->assertFalse($entry->hasTag('security'));
		$this->assertFalse($entry->hasApp('portal'));
	}

	public function testDeserializesNestedAccessAndLogObjects(): void {
		$entry = \Base3\Xrm\XrmEntry::unserialize(json_encode([
			'id' => 42, 'name' => 'File', 'type' => 'doc', 'data' => 'payload', 'archive' => 1,
			'etag' => 'etag', 'created' => 'created', 'changed' => 'changed',
			'alloc' => ['xxtatest'], 'xrmnames' => ['de' => 'Dokument'],
			'access' => [['mode' => 'read', 'usergroup' => 'user', 'id' => 3]],
			'log' => [['action' => 'created', 'user' => 3]]
		]));
		$this->assertInstanceOf(\Base3\Xrm\XrmEntry::class, $entry);
		$this->assertSame(42, $entry->id);
		$this->assertSame('File', $entry->name);
		$this->assertSame('doc', $entry->type);
		$this->assertSame(['test'], $entry->getTags());
		$this->assertSame('Dokument', $entry->xrmnames['de']);
		$this->assertInstanceOf(\Base3\Xrm\XrmEntryAccess::class, $entry->access[0]);
		$this->assertInstanceOf(\Base3\Xrm\XrmEntryLog::class, $entry->log[0]);
		$this->assertSame($entry, \Base3\Xrm\XrmEntry::unserialize($entry));
	}

}
