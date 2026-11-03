<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\Comparator;
use PHPUnit\Framework\TestCase;

final class ComparatorTestDouble3 {
			public int $v;
			public int $calls = 0;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int {
				$this->calls++;
				return $this->v <=> $other->v;
			}
		}
