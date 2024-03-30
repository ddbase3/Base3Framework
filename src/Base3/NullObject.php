<?php

namespace Base3;

class NullObject {

	public function __call($method, $args) {
		if (DEBUG) echo 'NullObject called.';
	}

}
