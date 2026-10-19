<?php declare(strict_types=1);

namespace Base3\Authorization\Dto;

use InvalidArgumentException;

/** A definitive authorization result; indeterminate must always be enforced as denied. */
final readonly class AuthorizationDecision {

	public const ALLOW = 'allow';
	public const DENY = 'deny';
	public const INDETERMINATE = 'indeterminate';

	public function __construct(public string $outcome, public string $reasonCode = '') {
		if (!in_array($outcome, [self::ALLOW, self::DENY, self::INDETERMINATE], true)) {
			throw new InvalidArgumentException('Unknown authorization outcome.');
		}
	}

	public static function allow(string $reasonCode = 'authorized'): self {
		return new self(self::ALLOW, $reasonCode);
	}

	public static function deny(string $reasonCode = 'not_authorized'): self {
		return new self(self::DENY, $reasonCode);
	}

	public static function indeterminate(string $reasonCode = 'authority_unavailable'): self {
		return new self(self::INDETERMINATE, $reasonCode);
	}

	public function isAllowed(): bool {
		return $this->outcome === self::ALLOW;
	}
}
