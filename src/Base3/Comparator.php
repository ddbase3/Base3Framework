<?php

namespace Base3;

class Comparator {

	public static function sort(&$array) {
		usort($array, function($a, $b) {
			return $a->compareTo($b);
		});
	}

}
