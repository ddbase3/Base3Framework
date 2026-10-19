<?php declare(strict_types=1);

namespace Base3\Authorization\Api;

use Base3\Api\IBase;
use Base3\Usermanager\Permission;

/** Plugin-discoverable feature description, not a second authorization decision service. */
interface IFeature extends IBase {

	/** @return string Non-empty human-readable feature label for administrative UIs */
	public function getLabel(): string;

	/** @return string Plain-language description of the feature's protected operations */
	public function getDescription(): string;

	/**
	 * Describes the real Permission checks protecting this feature. A feature can own several
	 * permissions; duplicate ownership between features is an error, not an implicit union.
	 *
	 * @return list<Permission> Non-empty, stable permission list with no runtime target
	 */
	public function getPermissions(): array;

	/**
	 * Returns the default BASE3 audience expression when a host has no explicit override.
	 * The same read-only IUserCriterion expression evaluator is used by group membership and
	 * feature access. Feature expressions may additionally contain ['group' => 'logical-name'].
	 * A configured empty expression denies access; native host permissions remain independent.
	 *
	 * @return array<string,mixed> Criterion expression or [] to deny access by default
	 */
	public function getDefaultAudience(): array;
}
