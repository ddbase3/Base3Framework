<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IOutput;

abstract class AbstractOutput implements IOutput {

	// Implementation of IBase

	public static function getName(): string {
		return strtolower($this->getClassName());
	}

	// Implementation of IOutput

        public function getHelp(): string {
                return 'Help of ' . $this->getClassName() . "\n";
        }

	// Private methods

	private function getClassName(): string {
		return (new \ReflectionClass($this))->getShortName();
	}
}

