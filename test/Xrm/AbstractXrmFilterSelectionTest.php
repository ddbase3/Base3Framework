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
use Base3\Test\Support\StaticClassMapStub;
use Base3\Core\Xrm\Cache\CacheXrm;
use Base3\CoreFoundation\Xrm\XrmFilter;
use PHPUnit\Framework\TestCase;

final class AbstractXrmFilterSelectionTest extends TestCase {

	private function createXrm(array $modules, ?RecordingXrmLogFixture $logger = null): CacheXrm {
		$previous = ServiceLocator::getInstance();
		$container = new ServiceLocator();
		$classmap = new StaticClassMapStub();
		foreach ($modules as $module) $classmap->register($module::getName(), $module);
		$container->set('classmap', $classmap, ServiceLocator::SHARED);
		if ($logger !== null) $container->set('logger', $logger, ServiceLocator::SHARED);
		ServiceLocator::useInstance($container);
		try { return new CacheXrm('test'); }
		finally { ServiceLocator::useInstance($previous); }
	}

	public function testRegistryWithoutMatchingModulesReturnsEmptyList(): void {
		$this->assertSame([], $this->createXrm([])->getEntriesIntern(null));
	}

	public function testModuleWithZeroPriorityIsNotSelected(): void {
		$module = new XrmFilterPriorityFixture(0, ['never']);
		$this->assertSame([], $this->createXrm([$module])->getEntriesIntern(new XrmFilter('type', 'eq', 'page')));
		$this->assertSame(1, $module->matches);
		$this->assertSame([], $module->requests);
	}

	public function testNegativePriorityCannotWinSelection(): void {
		$module = new XrmFilterPriorityFixture(-10, ['never']);
		$this->assertSame([], $this->createXrm([$module])->getEntriesIntern(null));
		$this->assertSame([], $module->requests);
	}

	public function testHigherPriorityModuleWinsRegardlessOfRegistryOrder(): void {
		$low = new XrmFilterPriorityFixture(2, ['low']);
		$high = new XrmFilterPriorityFixture(50, ['high']);
		$middle = new XrmFilterPriorityFixture(10, ['middle']);
		$xrm = $this->createXrm([$low, $high, $middle]);
		$this->assertSame(['high'], $xrm->getEntriesIntern(null, true));
		$this->assertSame(1, $low->matches);
		$this->assertSame(1, $middle->matches);
		$this->assertSame([], $low->requests);
		$this->assertSame([], $middle->requests);
		$this->assertSame(true, $high->requests[0][2]);
		$this->assertSame($xrm, $high->requests[0][0]);
	}

	public function testEqualPriorityRetainsFirstMatchingModule(): void {
		$first = new XrmFilterPriorityFixture(8, ['first']);
		$second = new XrmFilterPriorityFixture(8, ['second']);
		$this->assertSame(['first'], $this->createXrm([$first, $second])->getEntriesIntern(null));
		$this->assertCount(1, $first->requests);
		$this->assertSame([], $second->requests);
	}

	public function testJsonFilterIsParsedBeforeMatchingAndPassedToSelectedModule(): void {
		$module = new XrmFilterPriorityFixture(1, ['found']);
		$xrm = $this->createXrm([$module]);
		$this->assertSame(['found'], $xrm->getEntriesIntern('{"attr":"name","op":"eq","val":"A","limit":4}', true));
		$this->assertInstanceOf(XrmFilter::class, $module->lastMatchFilter);
		$this->assertSame('name', $module->lastMatchFilter->attr);
		$this->assertSame(4, $module->lastMatchFilter->limit);
		$this->assertTrue($module->requests[0][2]);
	}

	public function testAssociativeArrayFilterIsPromotedToObject(): void {
		$module = new XrmFilterPriorityFixture(2, ['a']);
		$xrm = $this->createXrm([$module]);
		$this->assertSame(['a'], $xrm->getEntriesIntern(['attr'=>'type','op'=>'eq','val'=>'note']));
		$this->assertSame('type', $module->lastMatchFilter->attr);
		$this->assertFalse($module->requests[0][2]);
	}

	public function testObjectFilterIdentityIsPreserved(): void {
		$module = new XrmFilterPriorityFixture(1, ['a']);
		$filter = new XrmFilter('name', 'eq', 'Alpha');
		$this->createXrm([$module])->getEntriesIntern($filter);
		$this->assertSame($filter, $module->lastMatchFilter);
		$this->assertSame($filter, $module->requests[0][1]);
	}

	public function testInvalidJsonFilterDoesNotReachAnyModule(): void {
		$module = new XrmFilterPriorityFixture(1, ['unexpected']);
		$xrm = $this->createXrm([$module]);
		try {
			$xrm->getEntriesIntern('{"op":"eq"}');
			$this->fail('Missing filter attribute was accepted.');
		} catch (\InvalidArgumentException $error) {
			$this->assertStringContainsString('missing "attr"', $error->getMessage());
		}
		$this->assertSame(0, $module->matches);
	}

	public function testPublicFilteredEntriesDelegatesWithFullEntryMode(): void {
		$entry = (object)['id' => 'a'];
		$module = new XrmFilterPriorityFixture(4, [$entry]);
		$xrm = $this->createXrm([$module]);
		$this->assertSame([$entry], $xrm->getFilteredEntries(new XrmFilter('ids', 'in', ['a'])));
		$this->assertFalse($module->requests[0][2]);
	}

	public function testOptionalQueryLoggingContainsInputAndResultCount(): void {
		$logger = new RecordingXrmLogFixture();
		$module = new XrmFilterPriorityFixture(4, ['one', 'two']);
		$xrm = $this->createXrm([$module], $logger);
		$property = new \ReflectionProperty(\Base3\CoreFoundation\Xrm\AbstractXrm::class, 'logging');
		$property->setValue($xrm, true);
		$entries = $xrm->getFilteredEntries(null);
		$this->assertSame(['one', 'two'], $entries);
		$this->assertCount(4, $logger->messages);
		$this->assertStringContainsString('getFilteredEntries', $logger->messages[0][0]);
		$this->assertStringContainsString('getEntriesIntern', $logger->messages[1][0]);
		$this->assertStringContainsString('"num":2', $logger->messages[3][0]);
		$this->assertSame(['scope' => 'xrm'], $logger->messages[3][1]);
	}
}

require_once __DIR__ . '/XrmFilterPriorityFixture.php';
require_once __DIR__ . '/RecordingXrmLogFixture.php';
require_once __DIR__ . '/../Support/StaticClassMapStub.php';
