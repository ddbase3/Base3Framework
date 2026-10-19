<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Usermanager\Criterion\Base3UserIdentityCriterion;
use Base3\Usermanager\User;
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

	public function testNeitherDomainOnlyNorUnknownPropertiesGrantAccess(): void {
		$criterion = new Base3UserIdentityCriterion();
		$user = User::fromArray(['id' => 44, 'userid' => 'other', 'email' => 'dev@qualitus.de']);
		self::assertFalse($criterion->matches($user, ['emails' => ['@qualitus.de']]));
		$this->expectException(InvalidArgumentException::class);
		$criterion->matches($user, ['ids' => [44]]);
	}
}
