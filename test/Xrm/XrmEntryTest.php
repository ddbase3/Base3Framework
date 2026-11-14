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

namespace Base3\Test\Xrm;

use PHPUnit\Framework\TestCase;

final class XrmEntryTest extends TestCase {

	public function testDefaultCollectionsAndTagLifecycle(): void {
		$entry = new \Base3\CoreFoundation\Xrm\XrmEntry();
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
		$entry = \Base3\CoreFoundation\Xrm\XrmEntry::unserialize(json_encode([
			'id' => 42, 'name' => 'File', 'type' => 'doc', 'data' => 'payload', 'archive' => 1,
			'etag' => 'etag', 'created' => 'created', 'changed' => 'changed',
			'alloc' => ['xxtatest'], 'xrmnames' => ['de' => 'Dokument'],
			'access' => [['mode' => 'read', 'usergroup' => 'user', 'id' => 3]],
			'log' => [['action' => 'created', 'user' => 3]]
		]));
		$this->assertInstanceOf(\Base3\CoreFoundation\Xrm\XrmEntry::class, $entry);
		$this->assertSame(42, $entry->id);
		$this->assertSame('File', $entry->name);
		$this->assertSame('doc', $entry->type);
		$this->assertSame(['test'], $entry->getTags());
		$this->assertSame('Dokument', $entry->xrmnames['de']);
		$this->assertInstanceOf(\Base3\CoreFoundation\Xrm\XrmEntryAccess::class, $entry->access[0]);
		$this->assertInstanceOf(\Base3\CoreFoundation\Xrm\XrmEntryLog::class, $entry->log[0]);
		$this->assertSame($entry, \Base3\CoreFoundation\Xrm\XrmEntry::unserialize($entry));
	}

}
