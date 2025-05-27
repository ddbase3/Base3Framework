<?php declare(strict_types=1);

namespace Base3\Util\DeviceDetect\Test;

use Base3\Page\Api\IPage;

class DeviceDetectTest implements IPage {

	// Implementation of IBase

	public static function getName(): string {
		return "devicedetecttest";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput($out = "html") {
		$str = '<h1>DeviceDetectTest</h1>';
		$dd = new \Base3\Util\DeviceDetect\DeviceDetect;
		$str .= $dd->getDevice();
		return $str;
	}

	public function getHelp() {
		return 'Help of DeviceDetectTest' . "\n";
	}

}
