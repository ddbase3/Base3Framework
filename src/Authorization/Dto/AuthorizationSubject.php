<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

/** A subject available for native permissions administration. */
final readonly class AuthorizationSubject {

	/** @param array<string,mixed> $metadata Non-authoritative display metadata, never a permission assertion */
	public function __construct(
		public SubjectReference $reference,
		public string $label,
		public bool $editable,
		public array $metadata = []
	) {}
}
