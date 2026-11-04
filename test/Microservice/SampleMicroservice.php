<?php declare(strict_types=1);

namespace Base3\Test\Microservice;

use Base3\Microservice\AbstractMicroservice;

final class SampleMicroservice extends AbstractMicroservice {
	public function sum($left,$right) { return $left + $right; }
	public function message($text) { return 'Hello '.$text; }
}
