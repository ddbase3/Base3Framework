<?php

namespace Util\NeuralNetwork;

interface INeuralNetworkActFunc {

	public function actfunc($val);
	public function difffunc($val);

}
