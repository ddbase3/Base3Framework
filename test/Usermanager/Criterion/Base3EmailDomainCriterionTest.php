<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Criterion;

use PHPUnit\Framework\TestCase;

final class Base3EmailDomainCriterionTest extends TestCase {

	public function testMatchesCompleteDomainButNotSubdomainsOrEmptyLocalPart(): void {
		$c = new \Base3\Usermanager\Criterion\Base3EmailDomainCriterion();
		$user = \Base3\Usermanager\User::fromArray(['email'=>' PERSON@EXAMPLE.ORG ']);
		$this->assertTrue($c->matches($user, ['domain'=>'example.org']));
		$this->assertFalse($c->matches($user, ['domain'=>'other.org']));
		$this->assertFalse($c->matches($user, ['domain'=>'sub.example.org']));
		$this->assertFalse($c->matches(null, ['domain'=>'example.org']));
		$this->assertFalse($c->matches(\Base3\Usermanager\User::fromArray(['email'=>'@example.org']), ['domain'=>'example.org']));
		$this->assertSame(['domain'], $c->getSchema()['required']);
	}

	public function testRejectsDomainWithAtSign(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Usermanager\Criterion\Base3EmailDomainCriterion())->matches(null, ['domain'=>'@example.org']);
	}

	public function testRejectsEmptyDomain(): void {
		$this->expectException(\InvalidArgumentException::class);
		(new \Base3\Usermanager\Criterion\Base3EmailDomainCriterion())->matches(null, ['domain'=>'']);
	}

}
