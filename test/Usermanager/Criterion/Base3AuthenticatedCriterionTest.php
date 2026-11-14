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

namespace Base3\Test\Usermanager\Criterion;

use PHPUnit\Framework\TestCase;

final class Base3AuthenticatedCriterionTest extends TestCase {

	public function testOnlyTrustedUserWithEmptyConfigurationMatches(): void {
		$c = new \Base3\Core\Usermanager\Criterion\Base3AuthenticatedCriterion();
		$this->assertSame('base3authenticatedcriterion', $c::getName());
		$this->assertTrue($c->matches(new \Base3\CoreFoundation\Usermanager\User(), []));
		$this->assertFalse($c->matches(null, []));
		$this->assertFalse($c->matches(new \Base3\CoreFoundation\Usermanager\User(), ['unexpected'=>true]));
		$this->assertFalse($c->getSchema()['additionalProperties']);
	}

}
