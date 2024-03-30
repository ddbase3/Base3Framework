<?php

namespace Util\NeuralNetwork;

class SigmoidNeuralNetworkActFunc implements INeuralNetworkActFunc {

	// Sigmoid
	public function actfunc($val) {  // activation function
		return 1 / ( 1 + exp(-$val) );
	}

	public function difffunc($val) {  // diff activation function
		return $this->actfunc($val) * (1 - $this->actfunc($val));
	}

}
