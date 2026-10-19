<?php declare(strict_types=1);

namespace Base3\Usermanager\Criterion;

use Base3\Usermanager\Api\IUserCriterion;
use Base3\Usermanager\User;
use InvalidArgumentException;

/** Matches explicitly listed native user IDs, without creating a parallel user registry. */
final class Base3UserIdCriterion implements IUserCriterion {
	public static function getName(): string { return 'base3useridcriterion'; }
	public function getSchema(): array {
		return ['type' => 'object', 'required' => ['user_ids'], 'properties' => [
			'user_ids' => ['type' => 'array', 'items' => ['type' => ['integer', 'string']], 'uniqueItems' => true]
		], 'additionalProperties' => false];
	}
	public function matches(?User $user, array $config): bool {
		$ids = $config['user_ids'] ?? null;
		if (!is_array($ids) || !array_is_list($ids)) throw new InvalidArgumentException('user_ids must be a list.');
		if ($user === null) return false;
		return in_array((string)$user->id, array_map('strval', $ids), true);
	}
}
