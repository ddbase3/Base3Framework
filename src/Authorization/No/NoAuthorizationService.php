<?php declare(strict_types=1);

namespace Base3\Authorization\No;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;
use InvalidArgumentException;

/** Deliberately denies access when no native authority is configured. */
final class NoAuthorizationService implements IAuthorizationService {

	public function getDefaultAuthority(): string { return 'unconfigured'; }

	public function decide(AuthorizationRequest $request): AuthorizationDecision {
		return AuthorizationDecision::deny('no_authority_configured');
	}

	public function decideMany(array $requests): array {
		$result = [];
		foreach ($requests as $key => $request) {
			if (!$request instanceof AuthorizationRequest) {
				throw new InvalidArgumentException('Expected AuthorizationRequest.');
			}
			$result[$key] = $this->decide($request);
		}
		return $result;
	}
}
