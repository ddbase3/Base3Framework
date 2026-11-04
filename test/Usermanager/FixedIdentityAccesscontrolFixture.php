<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Accesscontrol\Api\IAccesscontrol;

final class FixedIdentityAccesscontrolFixture implements IAccesscontrol {
	public function __construct(public mixed $identity = null) {}
	public function getUserId(): mixed { return $this->identity; }
	public function authenticate(): void {}
}
