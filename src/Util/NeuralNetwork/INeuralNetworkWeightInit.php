<?php declare(strict_types=1);

namespace Util\NeuralNetwork;

interface INeuralNetworkWeightInit {

	public function getWeights($neuralnetwork);

}
