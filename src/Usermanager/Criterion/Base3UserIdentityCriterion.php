<?php declare(strict_types=1);

namespace Base3\Usermanager\Criterion;

use Base3\Usermanager\Api\IUserCriterion;
use Base3\Usermanager\User;
use InvalidArgumentException;

/** Matches exact, host-supplied login names or complete email addresses; never matches a domain wildcard. */
final class Base3UserIdentityCriterion implements IUserCriterion {

	public static function getName(): string { return 'base3useridentitycriterion'; }

	/**
	 * Describes two independent allowlists. Either list may be empty; an entirely empty
	 * configuration matches nobody. The canonical login is taken from User::userid,
	 * not the host-specific numeric User::id. No ILIAS dependency exists here.
	 *
	 * @return array<string,mixed> Supported configuration schema, without persistence
	 */
	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'usernames' => ['type' => 'array', 'uniqueItems' => true, 'items' => ['type' => 'string', 'minLength' => 1], 'description' => 'Exact native account logins; case-sensitive'],
				'emails' => ['type' => 'array', 'uniqueItems' => true, 'items' => ['type' => 'string', 'minLength' => 3], 'description' => 'Exact complete email addresses; case-insensitive; not domains']
			],
			'additionalProperties' => false
		];
	}

	/**
	 * Compares only the trusted identity attributes exposed by IUsermanager. Usernames
	 * are exact and case-sensitive. Complete email addresses are compared ignoring ASCII
	 * letter case; their ownership/verification is solely the responsibility of the host.
	 * Both lists are OR-connected; no configured value means deny. This criterion never
	 * grants native administrative privileges and does not query or modify storage.
	 *
	 * @param User|null $user Trusted host identity; null represents an anonymous user
	 * @param array<string,mixed> $config Optional usernames and emails as lists of strings
	 * @return bool True only for a complete matching username or address
	 * @throws InvalidArgumentException For malformed or unsupported allowlist entries
	 */
	public function matches(?User $user, array $config): bool {
		if (array_diff(array_keys($config), ['usernames', 'emails']) !== []) throw new InvalidArgumentException('Unexpected identity criterion setting.');
		$usernames = $config['usernames'] ?? [];
		$emails = $config['emails'] ?? [];
		if (!is_array($usernames) || !array_is_list($usernames) || !is_array($emails) || !array_is_list($emails)) {
			throw new InvalidArgumentException('Identity allowlists must be lists.');
		}
		foreach ($usernames as $username) {
			if (!is_string($username) || $username === '' || $username !== trim($username)) throw new InvalidArgumentException('Identity usernames must be exact non-empty logins.');
		}
		foreach ($emails as $email) {
			if (!is_string($email) || $email === '' || $email !== trim($email) || substr_count($email, '@') !== 1 || str_starts_with($email, '@')) {
				throw new InvalidArgumentException('Identity emails must be complete addresses.');
			}
		}
		if ($user === null) return false;
		$login = $user->userid;
		if (is_string($login) && $login !== '' && in_array($login, $usernames, true)) return true;
		$email = $user->email;
		if (!is_string($email) || $email === '') return false;
		foreach ($emails as $allowedEmail) {
			if (strcasecmp($email, $allowedEmail) === 0) return true;
		}
		return false;
	}
}
