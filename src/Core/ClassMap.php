<?php declare(strict_types=1);

namespace Base3\Core;

class ClassMap extends AbstractClassMap {

	protected function getScanTargets(): array {
		return [
			["basedir" => DIR_SRC, "subdir" => "", "subns" => "Base3"]
		];
	}
}

