<?php

namespace Util\NeuralNetwork;

class NeuralNetworkNet {

	public $weights = array();
	public $prevlayer = null;
	public $nextlayer = null;

	public function toString() {
		$str = '  Net - #weights: ' . sizeof($this->weights) . "\n";
		foreach ($this->weights as $weight) $str .= $weight->toString();
		return $str;
	}

}
