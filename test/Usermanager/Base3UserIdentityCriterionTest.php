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

namespace Base3\Test\Usermanager;

use Base3\Core\Usermanager\Criterion\Base3UserIdentityCriterion;
use Base3\CoreFoundation\Usermanager\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class Base3UserIdentityCriterionTest extends TestCase {

	public function testExplicitUsernameOrCompleteEmailAcrossDifferentNumericUserIds(): void {
		$criterion = new Base3UserIdentityCriterion();
		$allowed = ['usernames' => ['base3-maintainer'], 'emails' => ['trusted@example.org']];
		$one = User::fromArray(['id' => '14', 'userid' => 'base3-maintainer', 'email' => 'unrelated@example.org']);
		$two = User::fromArray(['id' => '989', 'userid' => 'base3-maintainer', 'email' => 'other@example.org']);
		$three = User::fromArray(['id' => '302', 'userid' => 'else', 'email' => 'Trusted@Example.Org']);
		$outsider = User::fromArray(['id' => '14', 'userid' => 'other', 'email' => 'other@example.org']);
		self::assertTrue($criterion->matches($one, $allowed));
		self::assertTrue($criterion->matches($two, $allowed));
		self::assertTrue($criterion->matches($three, $allowed));
		self::assertFalse($criterion->matches($outsider, $allowed));
		self::assertFalse($criterion->matches(null, $allowed));
		self::assertFalse($criterion->matches($one, []));
	}

	public function testDomainOnlyIdentityEmailIsRejected(): void {
		$criterion = new Base3UserIdentityCriterion();
		$user = User::fromArray(['id' => 44, 'userid' => 'other', 'email' => 'dev@qualitus.de']);
		$this->expectException(InvalidArgumentException::class);
		$criterion->matches($user, ['emails' => ['@qualitus.de']]);
	}

	public function testUnknownIdentitySettingsAreRejected(): void {
		$criterion = new Base3UserIdentityCriterion();
		$user = User::fromArray(['id' => 44, 'userid' => 'other', 'email' => 'dev@qualitus.de']);
		$this->expectException(InvalidArgumentException::class);
		$criterion->matches($user, ['ids' => [44]]);
	}
}
