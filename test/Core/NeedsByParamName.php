<?php declare(strict_types=1);

namespace Base3Test\Core;

class NeedsByParamName { public DepA $a; public function __construct(DepA $custom) { $this->a = $custom; } }
