<?php

namespace Util\NeuralNetwork;

class NeuralNetworkNeuron {

	public $bias = false;
	public $inweights = array();
	public $outweights = array();
	public $sum = null;
	public $value = null;
	public $d = 0;

	public function toString() {
		$value = $this->value === null ? '-' : number_format($this->value, 2);
		if ($this->bias) {
			$str = '    Bias - value: ' . $value . "\n";
		} else {
			$sum = $this->sum === null ? '-' : number_format($this->sum, 2);
			$str = '    Neuron - sum: ' . $sum . ' - value: ' . $value . "\n";
		}
		return $str;
	}

}
