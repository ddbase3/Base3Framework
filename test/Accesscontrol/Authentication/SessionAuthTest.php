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

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class SessionAuthTest extends TestCase {

	public function testSessionAuthenticationLifecycleWithoutSessionStartup(): void {
		$session=$_SESSION ?? [];
		try {
			$_SESSION=[];
			$a=new \Base3\Core\Accesscontrol\Authentication\SessionAuth(new \Base3\Core\Session\NoSession\NoSession());
			$this->assertSame('sessionauth',$a::getName());
			$this->assertSame(['session_started'=>'Fail'],$a->checkDependencies());
			$this->assertNull($a->login());
			$a->keep('member');
			$this->assertSame('member',$a->login());
			$this->assertSame('member',$_SESSION['authentication']['userid']);
			$a->keep(null);
			$this->assertSame('member',$_SESSION['authentication']['userid']);
		} finally { $_SESSION=$session; }
	}

}
