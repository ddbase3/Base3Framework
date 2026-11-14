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

namespace Base3\Test\State\No;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Base3\Core\State\No\NoStateStore;

#[CoversClass(\Base3\Core\State\No\NoStateStore::class)]
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
