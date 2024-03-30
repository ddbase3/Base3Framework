<?php

namespace Util\NeuralNetwork;

class NeuralNetworkWeight {

	public $weight = null;
	public $delta = null;
	public $prevneuron = null;
	public $nextneuron = null;

	public function toString() {
		$weight = $this->weight == null ? '-' : number_format($this->weight, 2);
		$delta = $this->delta == null ? '-' : number_format($this->delta, 2);
		// $str = '    Weight - weight: ' . $weight . ' - delta: ' . $delta . "\n";
		$str = '    Weight - weight: ' . $weight . "\n";
		return $str;
	}

}
