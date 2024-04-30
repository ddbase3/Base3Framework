<?php

namespace Accesscontrol\No;

use Accesscontrol\Api\IAccesscontrol;

class NoAccesscontrol implements IAccesscontrol {

	// Implementation of IAccesscontrol

	public function getUserId() {
		return null;
	}
}
