<?php declare(strict_types=1);

namespace Base3;

use Api\IOutput;

abstract class AbstractOutput implements IOutput {

	// Implementation of IBase

	public function getName(): string {
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

