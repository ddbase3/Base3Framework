<?php declare(strict_types=1);

namespace Base3Test\Core;

class NeedsMockedInterface { public $m; public function __construct(IMockMe $m) { $this->m = $m; } }
