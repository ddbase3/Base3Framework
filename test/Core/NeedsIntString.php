<?php declare(strict_types=1);

namespace Base3Test\Core;

class NeedsIntString { public int $a; public string $b; public function __construct(int $a, string $b) { $this->a = $a; $this->b = $b; } }
