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

final class Base3EmailDomainCriterionTest extends TestCase {

	public function testMatchesCompleteDomainButNotSubdomainsOrEmptyLocalPart(): void {
		$c = new \Base3\Core\Usermanager\Criterion\Base3EmailDomainCriterion();
		$user = \Base3\CoreFoundation\Usermanager\User::fromArray(['email'=>' PERSON@EXAMPLE.ORG ']);
		$this->assertTrue($c->matches($user, ['domain'=>'example.org']));
		$this->assertFalse($c->matches($user, ['domain'=>'other.org']));
		$this->assertFalse($c->matches($user, ['domain'=>'sub.example.org']));
		$this->assertFalse($c->matches(null, ['domain'=>'example.org']));
		$this->assertFalse($c->matches(\Base3\CoreFoundation\Usermanager\User::fromArray(['email'=>'@example.org']), ['domain'=>'example.org']));
		$this->assertSame(['domain'], $c->getSchema()['required']);
	}

	public function testRejectsDomainWithAtSign(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Core\Usermanager\Criterion\Base3EmailDomainCriterion())->matches(null, ['domain'=>'@example.org']);
	}

	public function testRejectsEmptyDomain(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Core\Usermanager\Criterion\Base3EmailDomainCriterion())->matches(null, ['domain'=>'']);
	}

}
