<?php declare(strict_types=1);

namespace Base3\Core;

class NullObject {

	public function __call($method, $args) {
		if (getenv('DEBUG')) echo 'NullObject called.';
	}

}
