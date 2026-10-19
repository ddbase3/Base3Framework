<?php declare(strict_types=1);

namespace Base3\Test\Authorization;

use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\AuthorizationMultiplexer;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Authorization\Dto\AuthorizationRequest;
use Base3\Authorization\Dto\ResourceReference;
use Base3\Authorization\No\NoAuthorizationService;
use Base3\Usermanager\Permission;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuthorizationContractsTest extends TestCase {

	public function testUnknownAuthorityFailsClosed(): void {
		$host = $this->host('ilias', true);
		$mux = new AuthorizationMultiplexer('ilias', ['ilias' => $host]);
		self::assertSame('ilias', $mux->getDefaultAuthority());
		$request = new AuthorizationRequest('other', Permission::for('system', 'admin'));
		self::assertSame(AuthorizationDecision::INDETERMINATE, $mux->decide($request)->outcome);
		self::assertFalse($mux->decide($request)->isAllowed());
	}

	public function testDifferentAuthoritiesAreDispatchedIndependently(): void {
		$mux = new AuthorizationMultiplexer('ilias', [
			'ilias' => $this->host('ilias', true),
			'xrm:one' => $this->host('xrm:one', false)
		]);
		$requests = [
			'last' => new AuthorizationRequest('xrm:one', Permission::for('entry','read'), new ResourceReference('xrm:one','entry',12)),
			'first' => new AuthorizationRequest('ilias', Permission::for('ilias','read'), new ResourceReference('ilias','ref_id',5)),
			'missing' => new AuthorizationRequest('xrm:two', Permission::for('entry','read'))
		];
		$results = $mux->decideMany($requests);
		self::assertSame(array_keys($requests), array_keys($results));
		self::assertSame(AuthorizationDecision::DENY, $results['last']->outcome);
		self::assertSame(AuthorizationDecision::ALLOW, $results['first']->outcome);
		self::assertSame(AuthorizationDecision::INDETERMINATE, $results['missing']->outcome);
	}

	public function testNativeFailureNeverBecomesAuthorization(): void {
		$failed = new class implements IAuthorizationService {
			public function getDefaultAuthority(): string { return 'ilias'; }
			public function decide(AuthorizationRequest $request): AuthorizationDecision { throw new \RuntimeException('Native error'); }
			public function decideMany(array $requests): array { throw new \RuntimeException('Native error'); }
		};
		$mux = new AuthorizationMultiplexer('ilias', ['ilias' => $failed]);
		$request = new AuthorizationRequest('ilias', Permission::for('ilias', 'read'));
		self::assertSame(AuthorizationDecision::INDETERMINATE, $mux->decide($request)->outcome);
		self::assertSame(AuthorizationDecision::INDETERMINATE, $mux->decideMany(['one' => $request])['one']->outcome);
	}

	public function testResourceAuthoritiesCannotBeMixed(): void {
		$this->expectException(InvalidArgumentException::class);
		new AuthorizationRequest('ilias', Permission::for('entry','read'), new ResourceReference('xrm:one', 'entry', 5));
	}

	public function testMissingNativeServiceNeverGrants(): void {
		$service = new NoAuthorizationService();
		self::assertFalse($service->decide(new AuthorizationRequest('ilias', Permission::for('system','admin')))->isAllowed());
	}

	private function host(string $authority, bool $allowed): IAuthorizationService {
		return new class($authority, $allowed) implements IAuthorizationService {
			public function __construct(private string $authority, private bool $allowed) {}
			public function getDefaultAuthority(): string { return $this->authority; }
			public function decide(AuthorizationRequest $request): AuthorizationDecision {
				if ($request->authority !== $this->authority) return AuthorizationDecision::indeterminate('unknown_authority');
				return $this->allowed ? AuthorizationDecision::allow() : AuthorizationDecision::deny();
			}
			public function decideMany(array $requests): array {
				$out = [];
				foreach ($requests as $key => $request) $out[$key] = $this->decide($request);
				return $out;
			}
		};
	}
}
