<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

use InvalidArgumentException;

/** A principal identifier within the owning identity authority, not a globally meaningful user ID. */
final readonly class SubjectReference {

	public function __construct(
		public string $authority,
		public string $kind,
		public int|string $id
	) {
		if (trim($authority) === '' || !in_array($kind, ['user', 'group', 'role', 'agent', 'service'], true) || trim((string)$id) === '') {
			throw new InvalidArgumentException('A subject needs an authority, supported kind and non-empty ID.');
		}
	}
}
