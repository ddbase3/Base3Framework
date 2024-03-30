<?php

namespace Util\MobileDetect\Test;

use Page\Api\IPage;

class MobileDetectTest implements IPage {

	// Implementation of IBase

	public function getName() {
		return "mobiledetecttest";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput($out = "html") {
		$str = '<h1>MobileDetectTest</h1>';

		$md = new \Util\MobileDetect\MobileDetect;
		$str .= '<p>mobile? - ' . ( $md->isMobile() ? 'yes' : 'no' ) . '</p>';
		$str .= '<p>tablet? - ' . ( $md->isTablet() ? 'yes' : 'no' ) . '</p>';
		$str .= '<p>phone? - ' . ( $md->isMobile() && !$md->isTablet() ? 'yes' : 'no' ) . '</p>';
		$str .= '<p>ios? - ' . ( $md->isiOS() ? 'yes' : 'no' ) . '</p>';
		$str .= '<p>android? - ' . ( $md->isAndroidOS() ? 'yes' : 'no' ) . ' - ' . $md->version('Android') . '</p>';
		$str .= '<p>chrome? - ' . ( $md->is('Chrome') ? 'yes' : 'no' ) . '</p>';
		$str .= '<p>samsung? - ' . ( $md->isSamsung() ? 'yes' : 'no' ) . '</p>';
		$str .= '';  // version(Windows NT)

		return $str;
	}

	public function getHelp() {
		return 'Help of MobileDetectTest' . "\n";
	}

}
