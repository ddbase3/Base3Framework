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

namespace Base3\Test\Authorization\No;

use PHPUnit\Framework\TestCase;

final class NoAuthorizationServiceTest extends TestCase {

	public function testNeverAllowsOperationsWithoutNativeAuthority(): void {
		$s = new \Base3\Core\Authorization\No\NoAuthorizationService();
		$this->assertSame('unconfigured', $s->getDefaultAuthority());
		$request = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest('host', \Base3\CoreFoundation\Usermanager\Permission::for('course', 'read'));
		$decision = $s->decide($request);
		$this->assertFalse($decision->isAllowed());
		$this->assertSame('no_authority_configured', $decision->reasonCode);
		$batch = $s->decideMany(['a' => $request]);
		$this->assertSame(['a'], array_keys($batch));
		$this->assertSame($decision->reasonCode, $batch['a']->reasonCode);
	}

	public function testBatchRejectsNonRequestObjects(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Core\Authorization\No\NoAuthorizationService())->decideMany([false]);
	}

}
