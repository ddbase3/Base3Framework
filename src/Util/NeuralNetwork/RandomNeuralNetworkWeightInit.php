<?php

namespace Util\NeuralNetwork;

class RandomNeuralNetworkWeightInit implements INeuralNetworkWeightInit {

	private $min;
	private $max;
	private $factor;

	// min/max for random weights
	public function __construct($min = -1, $max = 1, $factor = 10) {
		$this->min = $min;
		$this->max = $max;
		$this->factor = $factor;
	}

	public function getWeights($neuralnetwork) {
		$weights = array();
		for ($ilayer = 0; $ilayer < sizeof($neuralnetwork->data["layers"]) - 1; $ilayer++)
			for ($itoneuron = 0; $itoneuron < $neuralnetwork->data["layers"][$ilayer + 1]; $itoneuron++)
				for ($ifromneuron = 0; $ifromneuron < $neuralnetwork->data["layers"][$ilayer] + ($neuralnetwork->data["bias"] ? 1 : 0); $ifromneuron++) {
					$min = $this->min * $this->factor;
					$max = $this->max * $this->factor;
					$weights[$ilayer][$itoneuron][$ifromneuron] = mt_rand($min, $max) / $this->factor;  // erzeugt Werte -1.0 ... 1.0 mit Abständen 0.1 (bei $factor = 10)
				}
		return $weights;
	}

}
