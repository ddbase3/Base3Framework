<?php declare(strict_types=1);

namespace Base3\Usermanager\Criterion;

use Base3\Usermanager\Api\IUserCriterion;
use Base3\Usermanager\User;

/** Matches any authenticated user resolved by the active host IUsermanager. */
final class Base3AuthenticatedCriterion implements IUserCriterion {
	public static function getName(): string { return 'base3authenticatedcriterion'; }
	public function getSchema(): array { return ['type' => 'object', 'properties' => [], 'additionalProperties' => false]; }
	public function matches(?User $user, array $config): bool { return $config === [] && $user !== null; }
}
