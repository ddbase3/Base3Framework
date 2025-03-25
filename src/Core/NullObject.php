<?php declare(strict_types=1);

namespace Core;

class NullObject {

	public function __call($method, $args) {
		if (DEBUG) echo 'NullObject called.';
	}

}
