<?php declare(strict_types=1);

namespace Base3\Session\NoSession;

use Base3\Session\Api\ISession;

class NoSession implements ISession {

	public function started(): bool {
		return false;
	}
}
