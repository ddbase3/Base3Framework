<?php declare(strict_types=1);

namespace Base3\Test\Authorization;

use Base3\Usermanager\No\NoUsermanager;

final class PermissionUsermanagerStub extends NoUsermanager {

	public array $permissions = [];

	public function getPermissions() { return $this->permissions; }
}
