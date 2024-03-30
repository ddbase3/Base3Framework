<?php

namespace Util\NeuralNetwork;

class NeuralNetwork {

	public $data;

	public $layers = array();

	// TODO check input weights, damit es zu den Layern/Neuronen passt
	public function __construct($data = null) {
		$default = array(
			"actfunc" => new SigmoidNeuralNetworkActFunc,

			"epsilon" => .1,

			"layers" => array(2, 2, 2),
			"bias" => true,

			"weightinit" => new RandomNeuralNetworkWeightInit,
			"weights" => null	// must be defined, if weightinit == null
/*
			"weights" => array(	// no values for first layer, no values for biases
				array(
					array(.3, .8, .5),
					array(-.2, -.6, .7)
				),
				array(
					array(.2, .4, .3),
					array(.1, -.4, .9)
				)
			)
*/
		);
		$this->data = $data == null ? $default : array_merge($default, $data);

		if ($this->data["weights"] == null) $this->initWeights();

		$this->init();
	}

	private function initWeights() {
		if ($this->data["weightinit"] == null) return;
		$this->data["weights"] = $this->data["weightinit"]->getWeights($this);
	}

	private function init() {
		$first = false;
		$prevlayer = null;
		$ilayer = 0;
		foreach ($this->data["layers"] as $numneurons) {

			$layer = new NeuralNetworkLayer;
			if ($this->data["bias"] && $ilayer < sizeof($this->data["layers"]) - 1) {
				$neuron = new NeuralNetworkNeuron;
				$neuron->bias = true;
				$neuron->value = 1;
				$layer->neurons[] = $neuron;
			}
			for ($i = 0; $i < $numneurons; $i++) {
				$neuron = new NeuralNetworkNeuron;
				$layer->neurons[] = $neuron;
			}

			if ($first) {
				$net = new NeuralNetworkNet;
				$net->prevlayer = $prevlayer;
				$net->nextlayer = $layer;
				$prevlayer->outnet = $net;
				$layer->innet = $net;
				$inext = 0;
				foreach ($layer->neurons as $nextneuron) {
					if ($nextneuron->bias) continue;
					$iprev = 0;
					foreach ($prevlayer->neurons as $prevneuron) {
						$weight = new NeuralNetworkWeight;
						$weight->weight = $this->data["weights"][$ilayer - 1][$inext][$iprev];
						$weight->prevneuron = $prevneuron;
						$weight->nextneuron = $nextneuron;
						$prevneuron->outweights[] = $weight;
						$nextneuron->inweights[] = $weight;
						$net->weights[] = $weight;
						$iprev++;
					}
					$inext++;
				}
			}

			$prevlayer = $layer;
			$this->layers[] = $layer;
			$first = true;
			$ilayer++;
		}
		if ($nextneuron->bias) unset($this->layers[sizeof($this->layers) - 1]->neurons[0]);
	}

	public function actfunc($val) {  // activation function
		return $this->data["actfunc"]->actfunc($val);
	}

	public function difffunc($val) {  // diff activation function
		return $this->data["actfunc"]->difffunc($val);
	}

	public function train($indata, $outdata) {  // arrays
		$this->predict($indata);

		$n = sizeof($this->layers);

		for ($ilayer = $n - 1; $ilayer > 0; $ilayer--) {

			if ($ilayer == $n - 1) {
				$ineuron = 0;
				foreach ($this->layers[$ilayer]->neurons as $neuron) {
					if ($neuron->bias) continue;  // no inweights for bias

					$neuron->d = $this->difffunc($neuron->sum) * ($outdata[$ineuron] - $neuron->value);
					foreach ($neuron->inweights as $inweight) {
						$a = $inweight->prevneuron->value;
						$inweight->delta = $this->data["epsilon"] * $neuron->d * $a;
					}
					$ineuron++;
				}
			} else {
				foreach ($this->layers[$ilayer]->neurons as $neuron) {
					if ($neuron->bias) continue;  // no inweights for bias

					$dprevsum = 0;
					foreach ($neuron->outweights as $outweight)
						$dprevsum += $outweight->weight * $outweight->nextneuron->d;

					$d = $this->difffunc($neuron->sum) * $dprevsum;
					foreach ($neuron->inweights as $inweight) {
						$a = $inweight->prevneuron->value;
						$inweight->delta = $this->data["epsilon"] * $d * $a;
					}
				}
			}
		}

		for ($ilayer = 1; $ilayer < $n; $ilayer++) {

			$net = $this->layers[$ilayer]->innet;
			foreach ($net->weights as $weight) {
				$weight->weight += $weight->delta;
				$weight->delta = null;
			}

			foreach ($this->layers[$ilayer]->neurons as $neuron) $neuron->d = null;
		}

	}

	public function predict($indata) {  // array, returns output vector
		$n = sizeof($this->layers);

		$ineuron = 0;
		foreach ($this->layers[0]->neurons as $neuron) {
			if ($neuron->bias) continue;
			$neuron->value = $indata[$ineuron];
			$ineuron++;
		}

		for ($i = 1; $i < $n; $i++) {
			foreach ($this->layers[$i]->neurons as $neuron) {
				if ($neuron->bias) continue;
				$sum = 0;
				foreach ($neuron->inweights as $weight)
					$sum += $weight->prevneuron->value * $weight->weight;
				$neuron->sum = $sum;
				$neuron->value = $this->actfunc($sum);
			}
		}

		$outdata = array();
		foreach ($this->layers[$n - 1]->neurons as $neuron) $outdata[] = $neuron->value;
		return $outdata;
	}

	public function export() {
		$export = array(
			"layers" => array(),
			"bias" => !!$this->data["bias"],
			"weights" => array()
		);

		for ($i = 0; $i < sizeof($this->layers); $i++) {
			$layer = $this->layers[$i];
			$export["layers"][] = sizeof($layer->neurons) - ($this->data["bias"] && $i < sizeof($this->layers) - 1 ? 1 : 0);
			if ($i > 0) {
				$layerweights = array();
				foreach ($layer->neurons as $neuron) {
					if ($neuron->bias) continue;
					$neuronweights = array();
					foreach ($neuron->inweights as $inweight) $neuronweights[] = $inweight->weight;
					$layerweights[] = $neuronweights;
				}
				$export["weights"][] = $layerweights;
			}
		}
		return $export;
	}

	public function toString() {
		$str = 'NN - #layers: ' . sizeof($this->layers) . "\n";
		foreach ($this->layers as $layer) {
			if ($layer->innet != null) $str .= $layer->innet->toString();
			$str .= $layer->toString();
		}
		return $str;
	}

}
