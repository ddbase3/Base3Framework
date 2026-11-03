<?php declare(strict_types=1);

namespace Base3Test\Core;

class UnionDefault { public $v; public function __construct(DepA|DepB|null $dep = null) { $this->v = $dep; } }
