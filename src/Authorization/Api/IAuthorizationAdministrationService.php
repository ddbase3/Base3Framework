<?php declare(strict_types=1);

namespace Base3\Authorization\Api;

use Base3\Authorization\Dto\AuthorizationCapabilities;
use Base3\Authorization\Dto\AuthorizationGrant;
use Base3\Authorization\Dto\AuthorizationSubject;
use Base3\Authorization\Dto\ResourceReference;
use Base3\Authorization\Dto\SubjectReference;
use Base3\Usermanager\Permission;

/** Common rights-management contract; native authorities remain owners of grants and inheritance. */
interface IAuthorizationAdministrationService {

	/**
	 * Reports the native authority's capabilities for the current operator and optional resource.
	 * An authority that cannot support a UI operation MUST disclose that limitation; it must not fake
	 * data or silently store permissions in BASE3. The result may depend on native admin permissions.
	 *
	 * @param string $authority Exact registered authority including instance identity
	 * @param ResourceReference|null $resource Context to assess edit/read capabilities against
	 * @return AuthorizationCapabilities Supported operations and explicit limitations
	 * @throws \InvalidArgumentException For mismatched authorities
	 */
	public function describe(string $authority, ?ResourceReference $resource = null): AuthorizationCapabilities;

	/**
	 * Lists permission definitions valid for the native resource or global authority.
	 * This is metadata, NOT evidence that the current subject holds those permissions.
	 * An unauthorized caller must not receive protected administrative data.
	 *
	 * @param string $authority Registered native authority
	 * @param ResourceReference|null $resource Optional resource to restrict operations to its type
	 * @return list<Permission> Native permission definitions; empty only when no operations are available
	 * @throws \RuntimeException For unauthorized or unsupported catalog access
	 */
	public function getPermissions(string $authority, ?ResourceReference $resource = null): array;

	/**
	 * Lists subjects whose grants can be managed for a resource (e.g. native ILIAS roles).
	 * The native implementation determines the eligible local and inherited roles. This does
	 * not grant additional roles to a user and must not load an unbounded user directory.
	 *
	 * @param ResourceReference $resource A resource owned by this authority
	 * @return list<AuthorizationSubject> Native editable/read-only subjects with display metadata
	 * @throws \RuntimeException For unsupported scope or denied read permission
	 */
	public function getSubjects(ResourceReference $resource): array;

	/**
	 * Returns the native grants relevant to a resource. 'inherited = null' explicitly means the
	 * authority cannot reliably identify the original grant location; never manufacture ancestry.
	 * A native authority must avoid N+1 hierarchy traversal when an indexed/native read is available.
	 *
	 * @param ResourceReference $resource Exact resource in the owning authority
	 * @return list<AuthorizationGrant> Authoritatively read grants, without expanding them to unrelated users
	 * @throws \RuntimeException When the current operator is not allowed to inspect the grants
	 */
	public function getGrants(ResourceReference $resource): array;

	/**
	 * Atomically replaces direct native grants for exactly one editable subject and resource.
	 * Empty permissions deliberately revoke the subject's direct grants. Inherited and protected
	 * grants must not be silently modified. Every item must be a Permission with the correct native
	 * scope; the implementation validates resource, subject, operations, actor authorization and
	 * native restrictions before writes. Failed validation MUST NOT partially apply changes.
	 * The backend owns transaction and permission-cache invalidation semantics.
	 *
	 * @param ResourceReference $resource Native resource, never an unqualified numeric ID
	 * @param SubjectReference $subject Native role/user/group being edited, not the requesting actor
	 * @param list<Permission> $permissions Complete replacement list for that subject on this resource
	 * @return void
	 * @throws \InvalidArgumentException For invalid subject, permissions or mismatched authority
	 * @throws \RuntimeException For unauthorized, protected or otherwise unsupported writes
	 */
	public function replaceGrants(ResourceReference $resource, SubjectReference $subject, array $permissions): void;
}
