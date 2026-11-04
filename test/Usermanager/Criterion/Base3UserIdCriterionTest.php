<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Criterion;

use PHPUnit\Framework\TestCase;

final class Base3UserIdCriterionTest extends TestCase {

	public function testMatchesExactNativeIdentifiersIncludingIntegerZero(): void {
		$c = new \Base3\Usermanager\Criterion\Base3UserIdCriterion();
		$user = \Base3\Usermanager\User::fromArray(['id'=>0]);
		$this->assertTrue($c->matches($user, ['user_ids'=>[0, 17]]));
		$this->assertFalse($c->matches($user, ['user_ids'=>['00']]));
		$this->assertFalse($c->matches(null, ['user_ids'=>[0]]));
		$this->assertSame(['user_ids'], $c->getSchema()['required']);
	}

	public function testRejectsAssociativeIdentifiers(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Usermanager\Criterion\Base3UserIdCriterion())->matches(null, ['user_ids'=>['id'=>1]]);
	}

	public function testRejectsMissingIdentifierList(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Usermanager\Criterion\Base3UserIdCriterion())->matches(null, []);
	}

}
