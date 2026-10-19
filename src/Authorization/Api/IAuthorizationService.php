<?php declare(strict_types=1);

namespace Base3\Authorization\Api;

use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;

/** The sole authorization decision contract. Adapters must invoke their native authority, not reinterpret its hierarchy. */
interface IAuthorizationService {

	/**
	 * Returns the exact authorization authority for ordinary features in the current runtime.
	 * This value is chosen by project composition, never derived from user-supplied input.
	 * Callers of resource-specific operations MUST use the resource owner's explicit authority
	 * instead. In mixed projects this identifies the primary host, not all available authorities.
	 *
	 * @return string Non-empty registered authority/instance ID
	 */
	public function getDefaultAuthority(): string;

	/**
	 * Evaluates a validated request against the exact owning authority.
	 * Null subjects mean the current actor, not anonymous access by default. Unsupported scope, unknown
	 * identities and absent grants must never be allowed. Delegation must be independently authorized.
	 * This method does not execute an action, persist grants, or cache a decision beyond native guarantees.
	 * Callers MUST enforce only AuthorizationDecision::ALLOW and bind user-provided identifiers to trusted
	 * server-side identities. Native errors return INDETERMINATE without exposing secrets.
	 *
	 * @param AuthorizationRequest $request The immutable question, including resource and action parameters
	 * @return AuthorizationDecision ALLOW, DENY or INDETERMINATE with a safe reason code
	 */
	public function decide(AuthorizationRequest $request): AuthorizationDecision;

	/**
	 * Evaluates several independent authorization questions with backend-native preloading or batching.
	 * The result MUST preserve input keys and order; each result MUST have exactly the semantics of decide().
	 * An implementation must not retrieve all resources and filter them in PHP for large result sets:
	 * resource query providers remain responsible for native authorization-aware filtering.
	 * Invalid request entries cause InvalidArgumentException, never implicit access.
	 *
	 * @param array<int|string,AuthorizationRequest> $requests Requests keyed by their callers
	 * @return array<int|string,AuthorizationDecision> Decisions using the same keys as the input
	 * @throws \InvalidArgumentException If any element is not an AuthorizationRequest
	 */
	public function decideMany(array $requests): array;
}
