<?php declare(strict_types=1);

namespace Base3;

class NullObject {

	public function __call($method, $args) {
		if (DEBUG) echo 'NullObject called.';
	}

}
