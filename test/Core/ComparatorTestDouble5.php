<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Core\Comparator;
use PHPUnit\Framework\TestCase;

final class ComparatorTestDouble5 {
			public int $v;
			public function __construct(int $v) { $this->v = $v; }
			public function compareTo($other): int { return $this->v <=> $other->v; }
		}
