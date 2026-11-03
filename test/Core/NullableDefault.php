<?php declare(strict_types=1);

namespace Base3Test\Core;

class NullableDefault { public $v; public function __construct(?DepA $a = null) { $this->v = $a; } }
