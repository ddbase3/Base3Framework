<?php declare(strict_types=1);

namespace Base3;

use Api\IOutput;
use Api\ICheck;
use Base3\ServiceLocator;

class Check implements IOutput, ICheck {

	protected $servicelocator;

	private $checks;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public function getName(): string {
		return "check";
	}

	// Implementation of IOutput

	public function getOutput($out = "html"): string {

		if (!DEBUG) return '';

		$this->check();
		$out = '<html>';
		$out .= '<head>';
		$out .= '<style>* { font-family:Arial,sans-serif; }</style>';
		$out .= '</head>';
		$out .= '<body>';
		$out .= '<h1>Check</h1><table cellpadding="5">';
		foreach ($this->checks as $service) {
			$out .= '<tr>'
				. '<td bgcolor="#333333" style="color:#ffffff; font-weight:bold;">' . $service['title'] . '</td>'
				. '<td bgcolor="#333333" style="color:#ffffff; font-weight:bold;">' . $service['class'] . '</td>'
				. '</tr>';
			if ($service['data'] == null) {
				$out .= '<tr><td colspan="2" bgcolor="#ffcccc">not defined</td></tr>';
			} else if (is_array($service['data'])) {
				foreach ($service['data'] as $k => $v)
					$out .= '<tr>'
						. '<td bgcolor="#ccffcc">' . $k . '</td>'
						. '<td bgcolor="' . ($v == 'Ok' ? '#ccccff' : '#ffcccc') . '">' . $v . '</td>'
						. '</tr>';
			} else {
				$out .= '<tr><td colspan="2" bgcolor="#eeeeee">' . $service['data'] . '</td></tr>';
			}
		}
		$out .= '</table>';
		$out .= '</body>';
		$out .= '</html>';
		return $out;
	}

	public function getHelp(): string {
		return 'Help of Check' . "\n";
	}

	// Implementation of ICheck

	public function checkDependencies(): array {
		return array(
			"tmp_dir_writable" => is_writable(DIR_TMP) ? "Ok" : "tmp dir not writable"
		);
	}

	// Private methods

	private function check(): void {
		$this->checks = array();
		$services = $this->servicelocator->getServiceList();
		foreach ($services as $name) {
			$service = $this->servicelocator->get($name);
			$this->checkService($service, $name);
		}
	}

	private function checkService($service, string $name): void {
		switch (true) {

			case $service == null:
				$this->checks[] = array('title' => $name, 'class' => '', 'data' => 'no service');
				break;

			case is_callable($service):
				$instance = $service();
				if ($instance instanceof ICheck) $this->checkInstance($instance, $name);
				break;

			case $service instanceof ICheck:
				$this->checkInstance($service, $name);
				break;

			case is_array($service):
				foreach ($service as $key => $srv)
					$this->checkService($srv, $name . '[' . $key . ']');
				break;

			default:
				$this->checks[] = array('title' => $name, 'class' => '', 'data' => 'no check');
		}
	}

	private function checkInstance($instance, string $name) {
		$data = $instance->checkDependencies();
		$this->checks[] = array(
			'title' => $name,
			'class' => get_class($instance),
			'data' => empty($data) ? 'empty check' : $data
		);
	}

}

