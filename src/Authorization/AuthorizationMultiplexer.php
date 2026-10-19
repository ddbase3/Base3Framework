<?php declare(strict_types=1);

namespace Base3\Authorization;

use Base3\Authorization\Api\IAuthorizationAdministrationService;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationCapabilities;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Authorization\Dto\ResourceReference;
use Base3\Authorization\Dto\SubjectReference;
use InvalidArgumentException;
use RuntimeException;

/** Explicit authority/instance dispatcher used only by mixed project compositions. No policy evaluation. */
final class AuthorizationMultiplexer implements IAuthorizationService, IAuthorizationAdministrationService {

	/** @var array<string,IAuthorizationService> */
	private array $authorities;
	/** @var array<string,IAuthorizationAdministrationService> */
	private array $administrators;

	/**
	 * Registers exact native authorities without constructing a second authorization engine.
	 * Decision and administration implementations remain independently replaceable.
	 *
	 * @param string $defaultAuthority Default host authority chosen by project composition
	 * @param array<string,IAuthorizationService> $authorities Native decision services by instance
	 * @param array<string,IAuthorizationAdministrationService> $administrators Optional native administration services
	 * @throws InvalidArgumentException For unknown, empty or incompatible authority registrations
	 */
	public function __construct(
		private readonly string $defaultAuthority,
		array $authorities,
		array $administrators = []
	) {
		if (trim($defaultAuthority) === '' || !isset($authorities[$defaultAuthority])) {
			throw new InvalidArgumentException('A registered default authority is required.');
		}
		foreach ($authorities as $authority => $service) {
			if (!is_string($authority) || trim($authority) === '' || !$service instanceof IAuthorizationService) {
				throw new InvalidArgumentException('Invalid decision authority.');
			}
		}
		foreach ($administrators as $authority => $service) {
			if (!isset($authorities[$authority]) || !$service instanceof IAuthorizationAdministrationService) {
				throw new InvalidArgumentException('Administration must refer to a registered native authority.');
			}
		}
		$this->authorities = $authorities;
		$this->administrators = $administrators;
	}

	public function getDefaultAuthority(): string { return $this->defaultAuthority; }

	public function decide(AuthorizationRequest $request): AuthorizationDecision {
		$service = $this->authorities[$request->authority] ?? null;
		if ($service === null) return AuthorizationDecision::indeterminate('unknown_authority');
		try {
			return $service->decide($request);
		} catch (\Throwable) {
			return AuthorizationDecision::indeterminate('authority_unavailable');
		}
	}

	public function decideMany(array $requests): array {
		$batches = [];
		$results = [];
		foreach ($requests as $key => $request) {
			if (!$request instanceof AuthorizationRequest) throw new InvalidArgumentException('Expected AuthorizationRequest.');
			if (!isset($this->authorities[$request->authority])) {
				$results[$key] = AuthorizationDecision::indeterminate('unknown_authority');
				continue;
			}
			$batches[$request->authority][$key] = $request;
		}
		foreach ($batches as $authority => $batch) {
			try {
				$native = $this->authorities[$authority]->decideMany($batch);
				foreach ($batch as $key => $_) {
					$decision = $native[$key] ?? null;
					$results[$key] = $decision instanceof AuthorizationDecision
						? $decision : AuthorizationDecision::indeterminate('invalid_authority_response');
				}
			} catch (\Throwable) {
				foreach ($batch as $key => $_) $results[$key] = AuthorizationDecision::indeterminate('authority_unavailable');
			}
		}
		$ordered = [];
		foreach ($requests as $key => $_) $ordered[$key] = $results[$key];
		return $ordered;
	}

	public function describe(string $authority, ?ResourceReference $resource = null): AuthorizationCapabilities {
		return $this->admin($authority)->describe($authority, $resource);
	}

	public function getPermissions(string $authority, ?ResourceReference $resource = null): array {
		return $this->admin($authority)->getPermissions($authority, $resource);
	}

	public function getSubjects(ResourceReference $resource): array {
		return $this->admin($resource->authority)->getSubjects($resource);
	}

	public function getGrants(ResourceReference $resource): array {
		return $this->admin($resource->authority)->getGrants($resource);
	}

	public function replaceGrants(ResourceReference $resource, SubjectReference $subject, array $permissions): void {
		$this->admin($resource->authority)->replaceGrants($resource, $subject, $permissions);
	}

	private function admin(string $authority): IAuthorizationAdministrationService {
		return $this->administrators[$authority] ?? throw new RuntimeException('Native rights administration is unavailable for this authority.');
	}
}
