<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Definition;

use Base3\Usermanager\Criterion\Base3AnonymousCriterion;
use Base3\Usermanager\Criterion\Base3AuthenticatedCriterion;
use Base3\Usermanager\Definition\Base3AnonymousGroup;
use Base3\Usermanager\Definition\Base3AuthenticatedGroup;
use Base3\Usermanager\User;
use PHPUnit\Framework\TestCase;

final class Base3BuiltInGroupsTest extends TestCase {

	public function testAuthenticatedAndAnonymousAreMutuallyExclusive(): void {
		$authenticated = new Base3AuthenticatedGroup();
		$anonymous = new Base3AnonymousGroup();
		self::assertSame('authenticated', $authenticated->getGroup()->name);
		self::assertSame('anonymous', $anonymous->getGroup()->name);
		self::assertFalse($authenticated->isEditable());
		self::assertFalse($anonymous->isEditable());
		self::assertSame(['criterion' => Base3AuthenticatedCriterion::getName(), 'config' => []], $authenticated->getCriteria());
		self::assertSame(['criterion' => Base3AnonymousCriterion::getName(), 'config' => []], $anonymous->getCriteria());
		$user = User::fromArray(['id' => 42, 'userid' => 'alice']);
		self::assertTrue((new Base3AuthenticatedCriterion())->matches($user, []));
		self::assertFalse((new Base3AnonymousCriterion())->matches($user, []));
		self::assertFalse((new Base3AuthenticatedCriterion())->matches(null, []));
		self::assertTrue((new Base3AnonymousCriterion())->matches(null, []));
	}
}
