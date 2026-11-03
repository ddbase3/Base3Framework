<?php declare(strict_types=1);

namespace Base3Test\Core;

abstract class AbsWithMethod { abstract public function n(): int; public function ok(): string { return "ok"; } }
