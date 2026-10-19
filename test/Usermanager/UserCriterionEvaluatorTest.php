<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Api\IClassMap;
use Base3\Usermanager\Api\IUserCriterion;
use Base3\Usermanager\Criterion\Base3AuthenticatedCriterion;
use Base3\Usermanager\Criterion\Base3EmailDomainCriterion;
use Base3\Usermanager\User;
use Base3\Usermanager\UserCriterionEvaluator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserCriterionEvaluatorTest extends TestCase {

	public function testNestedCriteriaAndNegation(): void {
		$map = $this->createMock(IClassMap::class);
		$map->method('getInstanceByInterfaceName')->willReturnCallback(static function($interface, $name) {
			if ($interface !== IUserCriterion::class) return null;
			return match ($name) {
				'base3authenticatedcriterion' => new Base3AuthenticatedCriterion(),
				'base3emaildomaincriterion' => new Base3EmailDomainCriterion(),
				default => null
			};
		});
		$user = new User();
		$user->id = 11;
		$user->email = 'person@qualitus.de';
		$evaluator = new UserCriterionEvaluator($map);
		self::assertTrue($evaluator->matches($user, ['all' => [
			['criterion' => 'base3authenticatedcriterion', 'config' => []],
			['criterion' => 'base3emaildomaincriterion', 'config' => ['domain' => 'qualitus.de']]
		]]));
		self::assertFalse($evaluator->matches(null, ['criterion' => 'base3authenticatedcriterion', 'config' => []]));
	}

	public function testNegationOfEmptyExpressionCannotGrantEveryUser(): void {
		$evaluator = new UserCriterionEvaluator($this->createMock(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->matches(null, ['not' => []]);
	}

	public function testCriterionWithUnexpectedFieldsIsRejected(): void {
		$evaluator = new UserCriterionEvaluator($this->createMock(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->matches(null, ['criterion' => 'base3authenticatedcriterion', 'config' => [], 'extra' => 'unexpected']);
	}

	public function testFeatureAudienceMayCombineGroupsAndAttributeCriteria(): void {
		$map = $this->createMock(IClassMap::class);
		$map->method('getInstanceByInterfaceName')->willReturnCallback(static fn($interface, $name) =>
			$name === 'base3emaildomaincriterion' ? new Base3EmailDomainCriterion() : null
		);
		$user = new User();
		$user->id = 42;
		$user->email = 'person@qualitus.de';
		$evaluator = new UserCriterionEvaluator($map);
		$expression = ['any' => [
			['group' => 'development'],
			['criterion' => 'base3emaildomaincriterion', 'config' => ['domain' => 'qualitus.de']]
		]];
		self::assertTrue($evaluator->matches($user, $expression, ['authenticated']));
		$evaluator->validate($expression, ['development', 'authenticated']);
		self::assertFalse($evaluator->matches(null, ['group' => 'development'], ['authenticated']));
	}

	public function testGroupCriterionCannotReferenceAnOtherGroup(): void {
		$evaluator = new UserCriterionEvaluator($this->createMock(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->validate(['group' => 'development']);
	}

	public function testUnknownGroupCannotBeSavedInsideFeatureAudience(): void {
		$evaluator = new UserCriterionEvaluator($this->createMock(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->validate(['group' => 'unknown'], ['authenticated']);
	}

	public function testUnknownCriterionCannotBecomeAllowedByNegation(): void {
		$map = $this->createMock(IClassMap::class);
		$map->method('getInstanceByInterfaceName')->willReturn(null);
		$evaluator = new UserCriterionEvaluator($map);
		$this->expectException(InvalidArgumentException::class);
		$evaluator->matches(null, ['not' => ['criterion' => 'doesnotexist', 'config' => []]]);
	}
}
