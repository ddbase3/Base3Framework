<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

use InvalidArgumentException;

/** A resource is identified by its owning authorization authority, type and native ID. */
final readonly class ResourceReference {

	public function __construct(
		public string $authority,
		public string $type,
		public int|string $id
	) {
		if (trim($authority) === '' || trim($type) === '' || trim((string)$id) === '') {
			throw new InvalidArgumentException('A resource needs an authority, type and non-empty ID.');
		}
	}
}
