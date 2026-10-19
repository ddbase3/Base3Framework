<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

use Base3\Usermanager\Permission;

/** A grant reported by the native authority for a subject on a resource. */
final readonly class AuthorizationGrant {

	public function __construct(
		public SubjectReference $subject,
		public Permission $permission,
		public ResourceReference $resource,
		public bool $editable,
		public ?bool $inherited = null,
		public ?ResourceReference $origin = null
	) {}
}
