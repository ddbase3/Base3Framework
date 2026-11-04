<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Master;

use Base3\Core\ServiceLocator;
use Base3\Xrm\Master\MasterXrm;
use PHPUnit\Framework\TestCase;

final class MasterXrmTest extends TestCase {

	private function createMaster(array $backends): MasterXrm {
		$original = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$closures = [];
		foreach ($backends as $name=>$backend) $closures[$name] = static fn() => $backend;
		$container->set('xrms', $closures, ServiceLocator::PARAMETER);
		ServiceLocator::useInstance($container);
		try { $master = new MasterXrm(); }
		finally { ServiceLocator::useInstance($original); }
		return $master;
	}

	public function testEmptyRegistryReturnsNoEntriesAndFailedDependency(): void {
		$master = $this->createMaster([]);
		$this->assertNull($master->getXrmName());
		$this->assertSame(['depending_services'=>'Fail'], $master->checkDependencies());
		$this->assertSame([], $master->getEntries(['a']));
		$this->assertSame([], $master->getAllEntryIds());
		$this->assertSame([], $master->getAllocIds('a'));
		$this->assertFalse($master->delEntry('a'));
		$this->assertNull($master->setEntry((object)['id'=>null,'xrmnames'=>[]]));
	}

	public function testMultipleBackendsMergeEntryNamesAndDeduplicateById(): void {
		$a = new MasterRecordingXrm('one');
		$b = new MasterRecordingXrm('two');
		$a->entryBatch = [(object)['id'=>'a','xrmnames'=>['one']], (object)['id'=>'b','xrmnames'=>['one']]];
		$b->entryBatch = [(object)['id'=>'a','xrmnames'=>['two']]];
		$master = $this->createMaster(['one'=>$a,'two'=>$b]);
		$entries = $master->getEntries(['a','b']);
		$this->assertSame(['a','b'], array_keys($entries));
		$this->assertSame(['one','two'], array_values($entries['a']->xrmnames));
		$this->assertSame(['one'], $entries['b']->xrmnames);
		$this->assertSame(['getEntries',['a','b']], $a->calls[0]);
	}

	public function testSingleEntryMergesBackendOrigins(): void {
		$a = new MasterRecordingXrm('one');
		$b = new MasterRecordingXrm('two');
		$a->entry = (object)['id'=>'item','xrmnames'=>['one']];
		$b->entry = (object)['id'=>'item','xrmnames'=>['two']];
		$entry = $this->createMaster(['one'=>$a,'two'=>$b])->getEntry('item');
		$this->assertSame('item', $entry->id);
		$this->assertSame(['one','two'], array_values($entry->xrmnames));
	}

	public function testEntryMutationPropagatesGeneratedIdAndOrigins(): void {
		$a = new MasterRecordingXrm('one');
		$b = new MasterRecordingXrm('two');
		$result = $this->createMaster(['one'=>$a,'two'=>$b])->setEntry((object)['id'=>null,'xrmnames'=>[]]);
		$this->assertSame('generated', $result->id);
		$this->assertSame(['one','two'], array_values($result->xrmnames));
		$this->assertSame('generated', $b->calls[0][1]->id);
	}

	public function testDeleteAndMutationsAggregateAllBackendResults(): void {
		$a = new MasterRecordingXrm('one');
		$b = new MasterRecordingXrm('two');
		$a->deleteResult = false;
		$b->mutationResult = false;
		$master = $this->createMaster(['one'=>$a,'two'=>$b]);
		$this->assertTrue($master->delEntry('item',true));
		$this->assertSame(0,$master->addTag('item','tag'));
		$this->assertSame(0,$master->removeTag('item','tag'));
		$this->assertSame(0,$master->addApp('item','app'));
		$this->assertSame(0,$master->removeApp('item','app'));
		$this->assertSame(0,$master->addAlloc('a','b'));
		$this->assertSame(0,$master->removeAlloc('a','b'));
		$this->assertSame(['delEntry','item',true], $a->calls[0]);
		$this->assertSame(['delEntry','item',true], $b->calls[0]);
	}

	public function testEntryIdQueriesAreCombinedAndPassFilterDirection(): void {
		$a = new MasterRecordingXrm('one');
		$b = new MasterRecordingXrm('two');
		$a->allIds = ['a'];
		$b->allIds = ['b'];
		$a->filteredIds = ['a'];
		$b->filteredIds = ['b'];
		$a->allocations = ['linked'];
		$b->allocations = [];
		$master = $this->createMaster(['one'=>$a,'two'=>$b]);
		$this->assertSame(['a','b'], $master->getAllEntryIds());
		$this->assertSame(['a','b'], $master->getXrmEntryIds('other',true));
		$this->assertSame(['linked'], $master->getAllocIds('a'));
		$this->assertSame(['getXrmEntryIds','other',true], $b->calls[1]);
	}

	public function testFirstAvailableUserEntryIdIsUsed(): void {
		$a = new MasterRecordingXrm('one');
		$b = new MasterRecordingXrm('two');
		$b->userEntryId = 'user-entry';
		$this->assertSame('user-entry', $this->createMaster(['one'=>$a,'two'=>$b])->getUserEntryId());
		$this->assertNull($this->createMaster(['one'=>$a])->getUserEntryId());
	}
}

require_once __DIR__ . '/MasterRecordingXrm.php';
