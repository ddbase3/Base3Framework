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

namespace Base3\Test\Usermanager\Definition;

use PHPUnit\Framework\TestCase;

final class Base3AnonymousGroupTest extends TestCase {

	public function testLogicalAnonymousGroupHasFixedNonEditableCriteria(): void {
		$group=new \Base3\Core\Usermanager\Definition\Base3AnonymousGroup();
		$this->assertSame('base3anonymousgroup',$group::getName());
		$this->assertSame('anonymous',$group->getGroup()->name);
		$this->assertSame(0,$group->getGroup()->archive);
		$this->assertFalse($group->isEditable());
		$this->assertSame(['criterion'=>'base3anonymouscriterion','config'=>[]],$group->getCriteria());
	}

}
