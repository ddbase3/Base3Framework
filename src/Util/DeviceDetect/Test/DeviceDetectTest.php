<?php

namespace Util\DeviceDetect\Test;

use Page\Api\IPage;

class DeviceDetectTest implements IPage {

	// Implementation of IBase

	public function getName() {
		return "devicedetecttest";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput($out = "html") {
		$str = '<h1>DeviceDetectTest</h1>';
		$dd = new \Util\DeviceDetect\DeviceDetect;
		$str .= $dd->getDevice();
		return $str;
	}

	public function getHelp() {
		return 'Help of DeviceDetectTest' . "\n";
	}

}
