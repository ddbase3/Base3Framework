<?php declare(strict_types=1);

namespace Base3\Test\State\No;

use PHPUnit\Framework\TestCase;
use Base3\State\No\NoStateStore;

/**
 * @covers \Base3\State\No\NoStateStore
 */
class NoStateStoreTest extends TestCase {

	public function testHasAlwaysReturnsFalse(): void {
		$s = new NoStateStore();

		$this->assertFalse($s->has('any.key'));
		$this->assertFalse($s->has(''));
	}

	public function testGetAlwaysReturnsDefault(): void {
		$s = new NoStateStore();

		$this->assertNull($s->get('any.key'));
		$this->assertSame('d', $s->get('any.key', 'd'));

		$obj = new \stdClass();
		$this->assertSame($obj, $s->get('any.key', $obj));

		$arr = ['a' => 1];
		$this->assertSame($arr, $s->get('any.key', $arr));
	}

	public function testSetIsNoOpAndDoesNotChangeHasOrGet(): void {
		$s = new NoStateStore();

		$s->set('k1', 'v1');
		$s->set('k2', ['x' => 1], 10);

		$this->assertFalse($s->has('k1'));
		$this->assertFalse($s->has('k2'));

		$this->assertSame('d1', $s->get('k1', 'd1'));
		$this->assertSame('d2', $s->get('k2', 'd2'));
	}

	public function testDeleteAlwaysReturnsFalse(): void {
		$s = new NoStateStore();

		$this->assertFalse($s->delete('any.key'));
		$this->assertFalse($s->delete(''));
	}

	public function testSetIfNotExistsAlwaysReturnsTrueButStoresNothing(): void {
		$s = new NoStateStore();

		$this->assertTrue($s->setIfNotExists('k1', 'v1'));
		$this->assertTrue($s->setIfNotExists('k1', 'v2', 123));

		$this->assertFalse($s->has('k1'));
		$this->assertSame('d', $s->get('k1', 'd'));
	}

	public function testListKeysAlwaysReturnsEmptyArray(): void {
		$s = new NoStateStore();

		$this->assertSame([], $s->listKeys('jobs.'));
		$this->assertSame([], $s->listKeys(''));
	}

	public function testFlushIsNoOp(): void {
		$s = new NoStateStore();

		// Ensure method exists and does not throw.
		$s->flush();

		$this->assertTrue(true);
	}
}
