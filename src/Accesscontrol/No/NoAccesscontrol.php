<?php declare(strict_types=1);

namespace Accesscontrol\No;

use Accesscontrol\Api\IAccesscontrol;

class NoAccesscontrol implements IAccesscontrol {

	// Implementation of IAccesscontrol

	public function getUserId() {
		return null;
	}
}
