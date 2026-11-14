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

use Base3\Test\Support\StaticClassMapStub;
use Base3\Core\Usermanager\Criterion\Base3AnonymousCriterion;
use Base3\Core\Usermanager\Criterion\Base3AuthenticatedCriterion;
use Base3\Core\Usermanager\Criterion\Base3UserIdCriterion;
use Base3\CoreFoundation\Usermanager\User;
use Base3\Core\Usermanager\UserCriterionEvaluator;
use PHPUnit\Framework\TestCase;

final class UserCriterionEvaluatorCoverageTest extends TestCase {

	private function evaluator(): UserCriterionEvaluator {
		$map = new StaticClassMapStub();
		foreach ([new Base3AnonymousCriterion(), new Base3AuthenticatedCriterion(), new Base3UserIdCriterion()] as $criterion) {
			$map->register($criterion::getName(), $criterion);
		}
		return new UserCriterionEvaluator($map);
	}

	private function user(int $id = 42): User {
		$user = new User();
		$user->id = $id;
		return $user;
	}

	public function testEmptyExpressionNeverMatchesAnyActor(): void {
		$evaluator = $this->evaluator();
		$this->assertFalse($evaluator->matches(null, []));
		$this->assertFalse($evaluator->matches($this->user(), []));
		$evaluator->validate([]);
		$this->addToAssertionCount(1);
	}

	public function testGroupMatchingIsExactAndDoesNotInferMembership(): void {
		$evaluator = $this->evaluator();
		$this->assertTrue($evaluator->matches(null, ['group'=>'team'], ['team']));
		$this->assertFalse($evaluator->matches($this->user(), ['group'=>'team'], ['Team']));
		$this->assertFalse($evaluator->matches($this->user(), ['group'=>'team'], []));
		$evaluator->validate(['group'=>'team'], ['team']);
		$this->addToAssertionCount(1);
	}

	public function testAnyOperatorAcceptsFirstOrLaterMatchingGroup(): void {
		$evaluator = $this->evaluator();
		$expression = ['any'=>[['group'=>'team'], ['group'=>'development']]];
		$this->assertTrue($evaluator->matches(null, $expression, ['team']));
		$this->assertTrue($evaluator->matches(null, $expression, ['development']));
		$this->assertFalse($evaluator->matches(null, $expression, ['authenticated']));
		$evaluator->validate($expression, ['team','development']);
		$this->addToAssertionCount(1);
	}

	public function testAllOperatorRequiresEveryLeafToMatch(): void {
		$evaluator = $this->evaluator();
		$expression = ['all'=>[['group'=>'team'], ['criterion'=>'base3authenticatedcriterion','config'=>[]]]];
		$this->assertTrue($evaluator->matches($this->user(), $expression, ['team']));
		$this->assertFalse($evaluator->matches(null, $expression, ['team']));
		$this->assertFalse($evaluator->matches($this->user(), $expression, []));
		$evaluator->validate($expression, ['team']);
		$this->addToAssertionCount(1);
	}

	public function testNotOperatorInvertsKnownValidCriterionOnly(): void {
		$evaluator = $this->evaluator();
		$expression = ['not'=>['criterion'=>'base3authenticatedcriterion','config'=>[]]];
		$this->assertTrue($evaluator->matches(null, $expression));
		$this->assertFalse($evaluator->matches($this->user(), $expression));
		$evaluator->validate($expression);
		$this->addToAssertionCount(1);
	}

	public function testRegisteredAnonymousCriterionDoesNotMatchAuthenticatedUser(): void {
		$evaluator = $this->evaluator();
		$leaf = ['criterion'=>'base3anonymouscriterion','config'=>[]];
		$this->assertTrue($evaluator->matches(null, $leaf));
		$this->assertFalse($evaluator->matches($this->user(), $leaf));
	}

	public function testUserIdPredicateAcceptsExplicitNativeIdentifiersOnly(): void {
		$evaluator = $this->evaluator();
		$leaf = ['criterion'=>'base3useridcriterion','config'=>['user_ids'=>[7, '42']]];
		$this->assertTrue($evaluator->matches($this->user(42), $leaf));
		$this->assertFalse($evaluator->matches($this->user(1), $leaf));
		$this->assertFalse($evaluator->matches(null, $leaf));
		$evaluator->validate($leaf);
		$this->addToAssertionCount(1);
	}

	public function testValidationRejectsMissingRequiredUserIdList(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['criterion'=>'base3useridcriterion','config'=>[]]);
	}

	public function testValidationRejectsDuplicateUserIds(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['criterion'=>'base3useridcriterion','config'=>['user_ids'=>[42,42]]]);
	}

	public function testValidationRejectsBooleanUserIds(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['criterion'=>'base3useridcriterion','config'=>['user_ids'=>[true]]]);
	}

	public function testValidationRejectsUnexpectedUserIdConfigProperties(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['criterion'=>'base3useridcriterion','config'=>['user_ids'=>[1], 'privileged'=>true]]);
	}

	public function testValidationRejectsListAsCriterionConfiguration(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['criterion'=>'base3useridcriterion','config'=>[1,2]]);
	}

	public function testEvaluationRejectsMalformedCriterionConfiguration(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches($this->user(), ['criterion'=>'base3useridcriterion','config'=>'not-an-array']);
	}

	public function testCompoundAnyMustHaveNonEmptyList(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches(null, ['any'=>[]]);
	}

	public function testCompoundAllMustHaveListNotMap(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['all'=>['leaf'=>['criterion'=>'base3anonymouscriterion']]]);
	}

	public function testCompoundNestedItemMustBeObject(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches(null, ['any'=>['invalid']]);
	}

	public function testAmbiguousLogicalOperatorsCannotBeEvaluated(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches(null, ['all'=>[['group'=>'a']], 'any'=>[['group'=>'b']]]);
	}

	public function testUnknownLogicalOperatorIsRejected(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['xor'=>[['group'=>'a']]], ['a']);
	}

	public function testGroupLeafWithOtherKeysIsRejected(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches(null, ['group'=>'team', 'config'=>[]], ['team']);
	}

	public function testCriterionLeafWithOtherKeysIsRejected(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['criterion'=>'base3anonymouscriterion','config'=>[],'role'=>'admin']);
	}

	public function testValidationRejectsUnknownCriterionEvenIfOtherLeafWouldMatch(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate(['any'=>[['group'=>'team'],['criterion'=>'missingpredicate','config'=>[]]]], ['team']);
	}

	public function testEvaluationRejectsUnknownCriterionInsideNegation(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches(null, ['not'=>['criterion'=>'missingpredicate','config'=>[]]]);
	}

	public function testNestedExpressionsBeyondMaximumDepthAreRejected(): void {
		$expression = ['group'=>'team'];
		for ($i = 0; $i < 14; $i++) $expression = ['not'=>$expression];
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->matches(null, $expression, ['team']);
	}

	public function testValidationAlsoRejectsExcessiveNesting(): void {
		$expression = ['group'=>'team'];
		for ($i = 0; $i < 14; $i++) $expression = ['not'=>$expression];
		$this->expectException(\InvalidArgumentException::class);
		$this->evaluator()->validate($expression, ['team']);
	}
}

require_once __DIR__ . '/../Support/StaticClassMapStub.php';
