<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

use Base3\Usermanager\Permission;
use InvalidArgumentException;

/** A single authorization question. No resource hierarchy or policies are reproduced here. */
final readonly class AuthorizationRequest {

	public Permission $permission;

	/**
	 * @param string $authority Exact authorization authority/instance registered by project composition
	 * @param Permission $permission Scope and operation, without an object target
	 * @param ResourceReference|null $resource Native resource, if the operation is resource-scoped
	 * @param SubjectReference|null $subject Trusted subject; null selects the current authenticated actor
	 * @param array<string,mixed> $parameters Validated action parameters, never an alternative identity source
	 * @param array<string,mixed> $context Trusted request attributes from the host or service
	 * @param SubjectReference|null $onBehalfOf Delegated principal if the caller acts for somebody else
	 * @throws InvalidArgumentException For an empty authority, invalid permission or mismatched resource authority
	 */
	public function __construct(
		public string $authority,
		Permission $permission,
		public ?ResourceReference $resource = null,
		public ?SubjectReference $subject = null,
		public array $parameters = [],
		public array $context = [],
		public ?SubjectReference $onBehalfOf = null
	) {
		if (trim($authority) === '' || trim((string)$permission->scope) === '' || trim((string)$permission->permission) === '') {
			throw new InvalidArgumentException('Authorization requires an authority, scope and operation.');
		}
		if ($resource !== null && $resource->authority !== $authority) {
			throw new InvalidArgumentException('The resource and request must have the same authority.');
		}
		$this->permission = clone $permission;
	}
}
