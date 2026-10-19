<?php declare(strict_types=1);

namespace Base3\Usermanager\Api;

use Base3\Api\IBase;
use Base3\Api\ISchemaProvider;
use Base3\Usermanager\User;

/** Plugin-discoverable, read-only predicate for BASE3 group membership; never grants a native permission. */
interface IUserCriterion extends IBase, ISchemaProvider {

	/**
	 * Evaluates one criterion against a trusted user supplied by the active IUsermanager.
	 * Null denotes an anonymous principal. It must be deterministic for its inputs and must not
	 * mutate users, roles, groups, permissions or persistence. Host-derived attributes are resolved
	 * by the implementation via injected native dependencies where needed. Values in $config
	 * conform to getSchema(). A missing value does not become a wildcard allow.
	 *
	 * @param User|null $user User data from the authoritative user manager, or null for anonymous
	 * @param array<string,mixed> $config Configuration provided by the group definition or host settings
	 * @return bool True only if this individual criterion matches
	 * @throws \InvalidArgumentException For invalid configuration values
	 */
	public function matches(?User $user, array $config): bool;
}
