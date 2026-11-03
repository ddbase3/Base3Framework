<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\Comparator;
use PHPUnit\Framework\TestCase;

final class ComparatorTest extends TestCase {

	public function testSortCallsCompareToAndSortsAscending(): void {
		$a = new ComparatorTestDouble1(3);

		$b = new ComparatorTestDouble2(1);

		$c = new ComparatorTestDouble3(2);

		$arr = [$a, $b, $c];

		Comparator::sort($arr);

		$this->assertSame([1, 2, 3], [$arr[0]->v, $arr[1]->v, $arr[2]->v]);

		// At least some compareTo calls must have happened during usort.
		$this->assertGreaterThan(0, $a->calls + $b->calls + $c->calls);
	}

	public function testSortKeepsArrayByReferenceAndWorksOnAlreadySorted(): void {
		$x = new ComparatorTestDouble4(1);

		$y = new ComparatorTestDouble5(2);

		$arr = [$x, $y];

		Comparator::sort($arr);

		$this->assertSame([1, 2], [$arr[0]->v, $arr[1]->v]);
	}
}

require_once __DIR__ . '/ComparatorTestDouble1.php';

require_once __DIR__ . '/ComparatorTestDouble2.php';

require_once __DIR__ . '/ComparatorTestDouble3.php';

require_once __DIR__ . '/ComparatorTestDouble4.php';

require_once __DIR__ . '/ComparatorTestDouble5.php';
