<?php

namespace Util\NeuralNetwork;

class NeuralNetworkLayer {

	public $neurons = array();
	public $innet = null;
	public $outnet = null;

	public function toString() {
		$str = '  Layer - #neurons: ' . sizeof($this->neurons) . "\n";
		foreach ($this->neurons as $neuron) $str .= $neuron->toString();
		return $str;
	}

}
