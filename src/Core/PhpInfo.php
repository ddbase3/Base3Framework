<?php declare(strict_types=1);

namespace Core;

use Api\IOutput;

class PhpInfo implements IOutput {

	public function __construct() {
	}

	// Implementation of IBase

	public function getName() {
		return "phpinfo";
	}

	// Implementation of IOutput

	public function getOutput($out = "html") {

                if (!DEBUG) return '';

		return phpinfo();
	}

	public function getHelp() {
		return 'Shows output of phpinfo();' . "\n";
	}

}
