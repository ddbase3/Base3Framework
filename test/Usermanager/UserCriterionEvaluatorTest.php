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

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Usermanager\Api\IUserCriterion;
use Base3\Core\Usermanager\Criterion\Base3AuthenticatedCriterion;
use Base3\Core\Usermanager\Criterion\Base3EmailDomainCriterion;
use Base3\CoreFoundation\Usermanager\User;
use Base3\Core\Usermanager\UserCriterionEvaluator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserCriterionEvaluatorTest extends TestCase {

	public function testNestedCriteriaAndNegation(): void {
		$map = $this->createStub(IClassMap::class);
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
		$evaluator = new UserCriterionEvaluator($this->createStub(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->matches(null, ['not' => []]);
	}

	public function testCriterionWithUnexpectedFieldsIsRejected(): void {
		$evaluator = new UserCriterionEvaluator($this->createStub(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->matches(null, ['criterion' => 'base3authenticatedcriterion', 'config' => [], 'extra' => 'unexpected']);
	}

	public function testFeatureAudienceMayCombineGroupsAndAttributeCriteria(): void {
		$map = $this->createStub(IClassMap::class);
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
		$evaluator = new UserCriterionEvaluator($this->createStub(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->validate(['group' => 'development']);
	}

	public function testUnknownGroupCannotBeSavedInsideFeatureAudience(): void {
		$evaluator = new UserCriterionEvaluator($this->createStub(IClassMap::class));
		$this->expectException(InvalidArgumentException::class);
		$evaluator->validate(['group' => 'unknown'], ['authenticated']);
	}

	public function testUnknownCriterionCannotBecomeAllowedByNegation(): void {
		$map = $this->createStub(IClassMap::class);
		$map->method('getInstanceByInterfaceName')->willReturn(null);
		$evaluator = new UserCriterionEvaluator($map);
		$this->expectException(InvalidArgumentException::class);
		$evaluator->matches(null, ['not' => ['criterion' => 'doesnotexist', 'config' => []]]);
	}
}
