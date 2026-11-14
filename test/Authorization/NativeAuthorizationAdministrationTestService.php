<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Authorization;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationAdministrationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationCapabilities;
use Base3\CoreFoundation\Authorization\Dto\ResourceReference;
use Base3\CoreFoundation\Authorization\Dto\SubjectReference;
use Base3\CoreFoundation\Usermanager\Permission;

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
