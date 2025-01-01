<?php declare(strict_types=1);

namespace Base3;

use Api\IOutput;
use Api\ICheck;

class Check implements IOutput, ICheck {

	protected $servicelocator;

	public function __construct() {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public function getName() {
		return "check";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {
		// $c = $this->servicelocator->get('check')->check();
		$c = $this->check();
		$out = '<html>';
		$out .= '<head>';
		$out .= '<style>* { font-family:Arial,sans-serif; }</style>';
		$out .= '</head>';
		$out .= '<body>';
		$out .= '<h1>Check</h1><table cellpadding="5">';
		foreach ($c as $service => $data) {
			$out .= '<tr><td colspan="2" bgcolor="#333333" style="color:#ffffff; font-weight:bold;">' . $service . '</td></tr>';
			if ($data == null) {
				$out .= '<tr><td colspan="2" bgcolor="#ffcccc">not defined</td></tr>';
			} else if (is_array($data)) {
				foreach ($data as $k => $v) $out .= '<tr><td bgcolor="#ccffcc">' . $k . '</td><td bgcolor="#ccccff">' . $v . '</td></tr>';
			} else {
				$out .= '<tr><td colspan="2" bgcolor="#eeeeee">' . $data . '</td></tr>';
			}
		}
		$out .= '</table>';
		$out .= '</body>';
		$out .= '</html>';
		return $out;
	}

	public function getHelp() {
		return 'Help of Check' . "\n";
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"tmp_dir_writable" => is_writable(DIR_TMP) ? "Ok" : "tmp dir not writable"
		);
	}

	// Private methods

	private function check() {
		$result = array();
		$services = $this->servicelocator->getServiceList();
		foreach ($services as $name) {
			$service = $this->servicelocator->get($name);
			if ($service == null) {
				$result[$name] = null;
				continue;
			}
			if (!($service instanceof \Api\ICheck)) {
				$result[$name] = "no check";
				continue;
			}
			$result[$name] = $service->checkDependencies();
			if (!sizeof($result[$name])) $result[$name] = "empty check";
		}
		return $result;
	}

}
