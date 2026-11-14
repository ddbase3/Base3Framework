<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Authorization;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\Core\Authorization\AuthorizationMultiplexer;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Authorization\Dto\ResourceReference;
use Base3\Core\Authorization\No\NoAuthorizationService;
use Base3\CoreFoundation\Usermanager\Permission;
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
		$failed = new AuthorizationContractsTestDouble1();
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
		return new AuthorizationContractsTestDouble2($authority, $allowed);
	}
}

require_once __DIR__ . '/AuthorizationContractsTestDouble1.php';

require_once __DIR__ . '/AuthorizationContractsTestDouble2.php';
