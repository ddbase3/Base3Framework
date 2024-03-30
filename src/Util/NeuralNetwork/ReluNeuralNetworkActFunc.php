<?php

namespace Util\NeuralNetwork;

class ReluNeuralNetworkActFunc implements INeuralNetworkActFunc {

	// ReLU (Rectified Linear Unit)
	public function actfunc($val) {
		return max(0, $val);
	}

	public function difffunc($val) {
		return $val > 0 ? 1 : 0;
	}

}
