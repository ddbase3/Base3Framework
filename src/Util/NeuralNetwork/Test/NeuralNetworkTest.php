<?php

namespace Util\NeuralNetwork\Test;

use Page\Api\IPage;

class NeuralNetworkTest implements IPage {

	// Implementation of IBase

	public function getName() {
		return "neuralnetworktest";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput($out = "html") {

		$str = '';
//		$str .= '<h1>NN Test</h1>';

/*
		$nn = new \Util\NeuralNetwork\NeuralNetwork;
		$str .= '<pre>' . $nn->toString() . '</pre>';
		$nn->train(array(.7, .6), array(.9, .2));
		$str .= '<pre>' . $nn->toString() . '</pre>';
*/

		$nn = new \Util\NeuralNetwork\NeuralNetwork(array(
			"layers" => array(2, 3, 5),
			"bias" => true /* ,
			"weights" => array(
				array(
					array(.3, .8, .5),
					array(-.2, -.6, .7),
					array(.5, -.2, .3)
				),
				array(
					array(.1, -.4, .9, -.2)
				)
			) */
		));
		for ($i = 0; $i < 5000; $i++) {
			$nn->train(array(0, 0), array(1, 0, 0, 0, 0));
			$nn->train(array(0, 1), array(1, 1, 0, 0, 0));
			$nn->train(array(1, 0), array(1, 1, 1, 0, 0));
			$nn->train(array(1, 1), array(1, 1, 1, 1, 0));
		}
		$str .= '<pre>' . $nn->toString() . '</pre>';

		$outdata = $nn->predict(array(0, 0));
		$outdata = array_map(function($o) { return number_format($o, 2); }, $outdata);
		$str .= '[0, 0] - [' . implode(", ", $outdata) . ']<br />';
		$outdata = $nn->predict(array(0, 1));
		$outdata = array_map(function($o) { return number_format($o, 2); }, $outdata);
		$str .= '[0, 1] - [' . implode(", ", $outdata) . ']<br />';
		$outdata = $nn->predict(array(1, 0));
		$outdata = array_map(function($o) { return number_format($o, 2); }, $outdata);
		$str .= '[1, 0] - [' . implode(", ", $outdata) . ']<br />';
		$outdata = $nn->predict(array(1, 1));
		$outdata = array_map(function($o) { return number_format($o, 2); }, $outdata);
		$str .= '[1, 1] - [' . implode(", ", $outdata) . ']<br />';

		return $str;
	}

	public function getHelp() {
		return 'Help of NeuralNetworkTest' . "\n";
	}

}
