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

namespace Base3\Test\Usermanager\Criterion;

use PHPUnit\Framework\TestCase;

final class Base3UserIdCriterionTest extends TestCase {

	public function testMatchesExactNativeIdentifiersIncludingIntegerZero(): void {
		$c = new \Base3\Core\Usermanager\Criterion\Base3UserIdCriterion();
		$user = \Base3\CoreFoundation\Usermanager\User::fromArray(['id'=>0]);
		$this->assertTrue($c->matches($user, ['user_ids'=>[0, 17]]));
		$this->assertFalse($c->matches($user, ['user_ids'=>['00']]));
		$this->assertFalse($c->matches(null, ['user_ids'=>[0]]));
		$this->assertSame(['user_ids'], $c->getSchema()['required']);
	}

	public function testRejectsAssociativeIdentifiers(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Core\Usermanager\Criterion\Base3UserIdCriterion())->matches(null, ['user_ids'=>['id'=>1]]);
	}

	public function testRejectsMissingIdentifierList(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Core\Usermanager\Criterion\Base3UserIdCriterion())->matches(null, []);
	}

}
