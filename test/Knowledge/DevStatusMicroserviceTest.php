<?php declare(strict_types=1);

namespace Base3\Test\Knowledge;

use Base3\Knowledge\DevStatusMicroservice;
use PHPUnit\Framework\TestCase;

final class DevStatusMicroserviceTest extends TestCase {

	private function withRecords(array $records): DevStatusMicroservice {
		// Initialize the read-only projection without changing the project's devstatus.json.
		$reflection = new \ReflectionClass(DevStatusMicroservice::class);
		$service = $reflection->newInstanceWithoutConstructor();
		$reflection->getProperty('data')->setValue($service, $records);
		return $service;
	}

	public function testScopesAndFieldsPreserveSourceOrder(): void {
		$service = $this->withRecords(['alpha'=>[['title'=>'A', 'state'=>1]], 'beta'=>[['name'=>'B']]]);
		$this->assertSame(['alpha', 'beta'], $service->getScopes());
		$this->assertSame(['title', 'state'], $service->getFields('alpha'));
		$this->assertSame(['name'], $service->getFields('beta'));
	}

	public function testMissingAndEmptyScopesReturnNull(): void {
		$service = $this->withRecords(['empty'=>[]]);
		$this->assertNull($service->getFields('unknown'));
		$this->assertNull($service->getFields('empty'));
		$this->assertNull($service->getData('unknown'));
		$this->assertSame([], $service->getData('empty'));
	}

	public function testFullDataAndRequestedFieldsAreReturnedWithoutOtherFields(): void {
		$records = [['id'=>1,'title'=>'A','private'=>'x'],['id'=>2,'title'=>'B','private'=>'y']];
		$service = $this->withRecords(['items'=>$records]);
		$this->assertSame($records, $service->getData('items'));
		$this->assertSame([['title'=>'A'],['title'=>'B']], $service->getData('items',['title']));
	}

	public function testEmptyFieldSelectionReturnsAllRecords(): void {
		$service = $this->withRecords(['items'=>[['id'=>1],['id'=>2]]]);
		$this->assertSame([['id'=>1],['id'=>2]], $service->getData('items',[]));
	}
}
