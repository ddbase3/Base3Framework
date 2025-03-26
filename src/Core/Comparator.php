<?php declare(strict_types=1);

namespace Base3\Core;

class Comparator {

	public static function sort(&$array) {
		usort($array, function($a, $b) {
			return $a->compareTo($b);
		});
	}

}
