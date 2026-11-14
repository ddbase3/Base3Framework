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

final class SingleSignOnAutoAuthTest extends TestCase {

	public function testAuthenticatedAndPostRequestsDoNotAttemptRedirect(): void {
		$old=$_SERVER;
		try {
			$c=new \Base3\Core\Configuration\ArrayConfiguration();
			$container=new \Base3\Test\Core\ContainerStub();
			$session=new \Base3\Core\Session\NoSession\NoSession();
			$a=new \Base3\Core\Accesscontrol\Authentication\SingleSignOnAutoAuth($c,$session,$container);
			$this->assertSame('singlesignonautoauth',$a::getName());
			$this->assertSame(['depending_services'=>'Fail'],$a->checkDependencies());
			$a->finish('member');
			$_SERVER['REQUEST_METHOD']='POST';
			$a->finish(null);
			unset($_SERVER['REQUEST_METHOD']);
			$_SERVER['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';
			$a->finish(null);
			$this->assertNull($a->login());
		} finally { $_SERVER=$old; }
	}

}

require_once __DIR__ . '/../../Core/ContainerStub.php';
