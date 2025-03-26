<?php declare(strict_types=1);

namespace Base3\Util\NeuralNetwork;

interface INeuralNetworkActFunc {

	public function actfunc($val);
	public function difffunc($val);

}
