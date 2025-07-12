<?php declare(strict_types=1);

namespace Base3\Accesscontrol\No;

use Base3\Accesscontrol\Api\IAccesscontrol;

class NoAccesscontrol implements IAccesscontrol {

	// Implementation of IAccesscontrol

	public function getUserId(): mixed {
		return null;
	}

	public function authenticate(): void {
		// no authentication needed
	}
}

