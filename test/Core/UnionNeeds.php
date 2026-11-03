<?php declare(strict_types=1);

namespace Base3Test\Core;

class UnionNeeds { public $v; public function __construct(DepA|DepB $dep) { $this->v = $dep; } }
