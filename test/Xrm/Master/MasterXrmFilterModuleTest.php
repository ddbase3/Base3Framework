<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Master;

use Base3\Core\ServiceLocator;
use Base3\Xrm\Master\MasterXrmFilterModule;
use Base3\Xrm\XrmFilter;
use PHPUnit\Framework\TestCase;

final class MasterXrmFilterModuleTest extends TestCase {

	private function createModule(array $backends): MasterXrmFilterModule {
		$original = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$closures = [];
		foreach ($backends as $name => $backend) $closures[$name] = static fn() => $backend;
		$container->set('xrms', $closures, ServiceLocator::PARAMETER);
		ServiceLocator::useInstance($container);
		try { return new MasterXrmFilterModule(); }
		finally { ServiceLocator::useInstance($original); }
	}

	public function testNameAndUnsupportedFilterProduceNoBackendSearch(): void {
		$backend = new MasterRecordingXrm('one');
		$module = $this->createModule(['one' => $backend]);
		$filter = new XrmFilter('secret', 'eq', 'value');
		$this->assertSame('masterxrmfiltermodule', $module::getName());
		$this->assertSame(0, $module->match($backend, $filter));
		$this->assertSame([], $module->getEntries($backend, $filter, true));
		$this->assertSame([], $backend->calls);
	}

	public function testIdSearchMergesMatchesFromAllBackends(): void {
		$first = new MasterRecordingXrm('first');
		$second = new MasterRecordingXrm('second');
		$first->filteredIds = ['a', 'b'];
		$second->filteredIds = ['b', 'c'];
		$module = $this->createModule(['first' => $first, 'second' => $second]);
		$filter = new XrmFilter('name', 'like', 'title', 1, 2);
		$this->assertSame(['a', 'b', 'b', 'c'], $module->getEntries($first, $filter, true));
		$this->assertSame('getEntriesIntern', $first->calls[0][0]);
		$this->assertSame('name', $first->calls[0][1]->attr);
		$this->assertSame('like', $first->calls[0][1]->op);
		$this->assertSame('title', $second->calls[0][1]->val);
		$this->assertTrue($second->calls[0][2]);
	}

	public function testFullEntriesAreRetrievedOnceAndSlicedAfterMerge(): void {
		$backend = new MasterRecordingXrm('backend');
		$backend->filteredIds = ['a', 'b', 'c'];
		$target = new MasterRecordingXrm('target');
		$target->entryBatch = [
			(object)['id' => 'a', 'xrmnames' => ['target']],
			(object)['id' => 'b', 'xrmnames' => ['target']],
			(object)['id' => 'c', 'xrmnames' => ['target']]
		];
		$module = $this->createModule(['backend' => $backend]);
		$filter = new XrmFilter('tag', 'eq', 'feature', 1, 1);
		$result = $module->getEntries($target, $filter);
		$this->assertCount(1, $result);
		$this->assertSame('b', $result[0]->id);
		$this->assertSame(['getEntries', ['a', 'b', 'c']], $target->calls[0]);
	}

	public function testOffsetWithoutLimitReturnsRemainingFullEntries(): void {
		$backend = new MasterRecordingXrm('backend');
		$backend->filteredIds = ['a', 'b', 'c'];
		$target = new MasterRecordingXrm('target');
		$target->entryBatch = [
			(object)['id' => 'a'], (object)['id' => 'b'], (object)['id' => 'c']
		];
		$module = $this->createModule(['backend' => $backend]);
		$result = $module->getEntries($target, new XrmFilter('archive', 'eq', false, 2));
		$this->assertCount(1, $result);
		$this->assertSame('c', $result[0]->id);
	}

	public function testNoMatchesReturnEmptyResultsForBothModes(): void {
		$backend = new MasterRecordingXrm('backend');
		$target = new MasterRecordingXrm('target');
		$module = $this->createModule(['backend' => $backend]);
		$filter = new XrmFilter('created', 'gt', 0);
		$this->assertSame([], $module->getEntries($target, $filter, true));
		$this->assertSame([], $module->getEntries($target, $filter, false));
		$this->assertSame(['getEntries', []], $target->calls[0]);
	}
}

require_once __DIR__ . '/MasterRecordingXrm.php';
