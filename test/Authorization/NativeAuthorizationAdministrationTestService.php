<?php declare(strict_types=1);

namespace Base3\Test\Authorization;

use Base3\Authorization\Api\IAuthorizationAdministrationService;
use Base3\Authorization\Dto\AuthorizationCapabilities;
use Base3\Authorization\Dto\ResourceReference;
use Base3\Authorization\Dto\SubjectReference;
use Base3\Usermanager\Permission;

final class NativeAuthorizationAdministrationTestService implements IAuthorizationAdministrationService {

	public array $changes = [];

	public function describe(string $authority, ?ResourceReference $resource = null): AuthorizationCapabilities {
		return new AuthorizationCapabilities($authority, true, false, true, ['role']);
	}
	public function getPermissions(string $authority, ?ResourceReference $resource = null): array {
		return [Permission::for('course', 'read')];
	}
	public function getSubjects(ResourceReference $resource): array { return []; }
	public function getGrants(ResourceReference $resource): array { return []; }
	public function replaceGrants(ResourceReference $resource, SubjectReference $subject, array $permissions): void {
		$this->changes[] = [$resource, $subject, $permissions];
	}
}
