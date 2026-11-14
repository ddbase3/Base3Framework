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

namespace Base3\Test\Core;

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
