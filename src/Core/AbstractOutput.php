<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IOutput;

abstract class AbstractOutput implements IOutput {

	// Implementation of IBase

	public static function getName(): string {
		return strtolower(static::getClassName());
	}

	// Implementation of IOutput

        public function getHelp(): string {
                return 'Help of ' . static::getClassName() . "\n";
        }

	// Private methods

	private static function getClassName(): string {
		return basename(str_replace('\\', '/', static::class));
	}
}

