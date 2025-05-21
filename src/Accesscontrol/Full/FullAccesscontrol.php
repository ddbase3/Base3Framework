<?php declare(strict_types=1);

namespace Base3\Accesscontrol\Full;

use Base3\Accesscontrol\Api\IAccesscontrol;

class FullAccesscontrol implements IAccesscontrol {

	// Implementation of IAccesscontrol

	public function getUserId() {
		return 'fullaccess';
	}
}
