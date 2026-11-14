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

use Base3\Core\ServiceLocator;
use Base3\Core\Xrm\DelegateXrmMicroservice;
use Base3\CoreFoundation\Xrm\XrmFilter;
use PHPUnit\Framework\TestCase;

final class DelegateXrmMicroserviceCoverageTest extends TestCase {

	private function createDelegate(XrmMicroserviceRecorder $recorder): DelegateXrmMicroservice {
		$previous = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$container->set('xrm', $recorder, ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new DelegateXrmMicroservice(); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testNameAndCurrentUserEntryAreDelegated(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses = ['getXrmName'=>'main','getUserEntryId'=>'current'];
		$delegate = $this->createDelegate($recorder);
		$this->assertSame('delegatexrmmicroservice', $delegate::getName());
		$this->assertSame('main', $delegate->getXrmName());
		$this->assertSame('current', $delegate->getUserEntryId());
		$this->assertSame([['getXrmName', []], ['getUserEntryId', []]], $recorder->calls);
	}

	public function testEntryReadsAreDelegatedWithExactIdentifiers(): void {
		$recorder = new XrmMicroserviceRecorder();
		$entry = (object)['id'=>'a'];
		$recorder->responses = ['getEntry'=>$entry,'getEntries'=>['a'=>$entry]];
		$delegate = $this->createDelegate($recorder);
		$this->assertSame($entry, $delegate->getEntry('a'));
		$this->assertSame(['a'=>$entry], $delegate->getEntries(['a','missing']));
		$this->assertSame([['getEntry',['a']],['getEntries',[['a','missing']]]], $recorder->calls);
	}

	public function testEntryMutationAndDeletionPreserveInputsAndResponses(): void {
		$recorder = new XrmMicroserviceRecorder();
		$entry = (object)['id'=>'a'];
		$recorder->responses = ['setEntry'=>$entry,'delEntry'=>true];
		$delegate = $this->createDelegate($recorder);
		$this->assertSame($entry, $delegate->setEntry($entry));
		$this->assertTrue($delegate->delEntry('a', true));
		$this->assertSame([['setEntry',[$entry]],['delEntry',['a',true]]], $recorder->calls);
	}

	public function testAllEntryAndAllocatedIdLookupsPreserveResults(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses = ['getAllEntryIds'=>['a'], 'getAllocIds'=>['b'], 'getXrmEntryIds'=>['x']];
		$delegate = $this->createDelegate($recorder);
		$this->assertSame(['a'], $delegate->getAllEntryIds());
		$this->assertSame(['b'], $delegate->getAllocIds('a'));
		$this->assertSame(['x'], $delegate->getXrmEntryIds('other', true));
		$this->assertSame([['getAllEntryIds',[]], ['getAllocIds',['a']], ['getXrmEntryIds',['other',true]]], $recorder->calls);
	}

	public function testFilteredEntriesKeepStructuredFilterUnmodified(): void {
		$recorder = new XrmMicroserviceRecorder();
		$filter = new XrmFilter('name','eq','entry');
		$recorder->responses['getFilteredEntries'] = ['entry'];
		$delegate = $this->createDelegate($recorder);
		$this->assertSame(['entry'], $delegate->getFilteredEntries($filter));
		$this->assertSame([['getFilteredEntries',[$filter]]], $recorder->calls);
	}

	public function testInternalFilterObjectIsSerializedForTransport(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses['getEntriesIntern'] = ['a'];
		$delegate = $this->createDelegate($recorder);
		$filter = new XrmFilter('type', 'eq', 'note', 2, 3);
		$this->assertSame(['a'], $delegate->getEntriesIntern($filter, true));
		$this->assertSame('getEntriesIntern', $recorder->calls[0][0]);
		$this->assertSame(true, $recorder->calls[0][1][1]);
		$this->assertSame('type', json_decode($recorder->calls[0][1][0], true)['attr']);
		$this->assertSame(3, json_decode($recorder->calls[0][1][0], true)['limit']);
	}

	public function testInternalFilterStringAndArrayAreNotSerializedTwice(): void {
		$recorder = new XrmMicroserviceRecorder();
		$delegate = $this->createDelegate($recorder);
		$delegate->getEntriesIntern('{"attr":"ids"}');
		$delegate->getEntriesIntern(['attr'=>'ids'], true);
		$this->assertSame('{"attr":"ids"}', $recorder->calls[0][1][0]);
		$this->assertSame(['attr'=>'ids'], $recorder->calls[1][1][0]);
		$this->assertSame([['getEntriesIntern',['{"attr":"ids"}',false]],['getEntriesIntern',[['attr'=>'ids'],true]]], $recorder->calls);
	}

	public function testConnectAndDisconnectPreserveBothDirections(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses = ['connect'=>true,'disconnect'=>false];
		$delegate = $this->createDelegate($recorder);
		$this->assertTrue($delegate->connect('a','b'));
		$this->assertFalse($delegate->disconnect('b','a'));
		$this->assertSame([['connect',['a','b']],['disconnect',['b','a']]], $recorder->calls);
	}

	public function testTagAndAppMutationsPreserveArguments(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses = ['addTag'=>true,'removeTag'=>false,'addApp'=>true,'removeApp'=>false];
		$delegate = $this->createDelegate($recorder);
		$this->assertTrue($delegate->addTag('a','alpha'));
		$this->assertFalse($delegate->removeTag('a','alpha'));
		$this->assertTrue($delegate->addApp('a','admin'));
		$this->assertFalse($delegate->removeApp('a','admin'));
		$this->assertSame([['addTag',['a','alpha']],['removeTag',['a','alpha']],['addApp',['a','admin']],['removeApp',['a','admin']]],$recorder->calls);
	}

	public function testAllocMutationsPreserveArguments(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses = ['addAlloc'=>true,'removeAlloc'=>true];
		$delegate = $this->createDelegate($recorder);
		$this->assertTrue($delegate->addAlloc('from','to'));
		$this->assertTrue($delegate->removeAlloc('from','to'));
		$this->assertSame([['addAlloc',['from','to']],['removeAlloc',['from','to']]],$recorder->calls);
	}

	public function testGetAccessUsesAuthoritativeBackendDecision(): void {
		$recorder = new XrmMicroserviceRecorder();
		$recorder->responses['getAccess'] = 'none';
		$delegate = $this->createDelegate($recorder);
		$entry = ['id'=>'protected','access'=>[]];
		$this->assertSame('none', $delegate->getAccess($entry));
		$this->assertSame([['getAccess',[$entry]]],$recorder->calls);
	}
}

require_once __DIR__ . '/XrmMicroserviceRecorder.php';
