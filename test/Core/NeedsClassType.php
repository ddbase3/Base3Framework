<?php declare(strict_types=1);

namespace Base3Test\Core;

class NeedsClassType { public DepA $a; public function __construct(DepA $a) { $this->a = $a; } }
