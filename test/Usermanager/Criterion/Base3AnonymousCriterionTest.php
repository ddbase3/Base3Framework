<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Criterion;

use PHPUnit\Framework\TestCase;

final class Base3AnonymousCriterionTest extends TestCase {

	public function testOnlyAnonymousActorWithEmptyConfigurationMatches(): void {
		$c = new \Base3\Usermanager\Criterion\Base3AnonymousCriterion();
		$this->assertSame('base3anonymouscriterion', $c::getName());
		$this->assertTrue($c->matches(null, []));
		$this->assertFalse($c->matches(null, ['unexpected'=>true]));
		$this->assertFalse($c->matches(new \Base3\Usermanager\User(), []));
		$this->assertFalse($c->getSchema()['additionalProperties']);
	}

}
