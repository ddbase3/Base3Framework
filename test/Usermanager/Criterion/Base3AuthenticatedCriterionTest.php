<?php declare(strict_types=1);

namespace Base3\Test\Usermanager\Criterion;

use PHPUnit\Framework\TestCase;

final class Base3AuthenticatedCriterionTest extends TestCase {

	public function testOnlyTrustedUserWithEmptyConfigurationMatches(): void {
		$c = new \Base3\Usermanager\Criterion\Base3AuthenticatedCriterion();
		$this->assertSame('base3authenticatedcriterion', $c::getName());
		$this->assertTrue($c->matches(new \Base3\Usermanager\User(), []));
		$this->assertFalse($c->matches(null, []));
		$this->assertFalse($c->matches(new \Base3\Usermanager\User(), ['unexpected'=>true]));
		$this->assertFalse($c->getSchema()['additionalProperties']);
	}

}
