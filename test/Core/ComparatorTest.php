<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\Comparator;
use PHPUnit\Framework\TestCase;

final class ComparatorTest extends TestCase {

	public function testSortCallsCompareToAndSortsAscending(): void {
		$a = new class(3) {
			public int $v;
			public int $calls = 0;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int {
				$this->calls++;
				return $this->v <=> $other->v;
			}
		};

		$b = new class(1) {
			public int $v;
			public int $calls = 0;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int {
				$this->calls++;
				return $this->v <=> $other->v;
			}
		};

		$c = new class(2) {
			public int $v;
			public int $calls = 0;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int {
				$this->calls++;
				return $this->v <=> $other->v;
			}
		};

		$arr = [$a, $b, $c];

		Comparator::sort($arr);

		$this->assertSame([1, 2, 3], [$arr[0]->v, $arr[1]->v, $arr[2]->v]);

		// At least some compareTo calls must have happened during usort.
		$this->assertGreaterThan(0, $a->calls + $b->calls + $c->calls);
	}

	public function testSortKeepsArrayByReferenceAndWorksOnAlreadySorted(): void {
		$x = new class(1) {
			public int $v;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int { return $this->v <=> $other->v; }
		};

		$y = new class(2) {
			public int $v;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int { return $this->v <=> $other->v; }
		};

		$arr = [$x, $y];

		Comparator::sort($arr);

		$this->assertSame([1, 2], [$arr[0]->v, $arr[1]->v]);
	}
}
