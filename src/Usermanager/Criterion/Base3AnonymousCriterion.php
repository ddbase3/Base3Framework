<?php declare(strict_types=1);

namespace Base3\Usermanager\Criterion;

use Base3\Usermanager\Api\IUserCriterion;
use Base3\Usermanager\User;

/** Matches the anonymous actor, not an arbitrary empty user ID. */
final class Base3AnonymousCriterion implements IUserCriterion {
	public static function getName(): string { return 'base3anonymouscriterion'; }
	public function getSchema(): array { return ['type' => 'object', 'properties' => [], 'additionalProperties' => false]; }
	public function matches(?User $user, array $config): bool { return $config === [] && $user === null; }
}
