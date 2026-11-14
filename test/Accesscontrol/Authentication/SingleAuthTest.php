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

final class SingleAuthTest extends TestCase {

	public function testNoLoginRequestDoesNotAuthenticate(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();
			\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('configuration', new \Base3\Core\Configuration\ArrayConfiguration(['singleauth'=>['user'=>'member','pass'=>sha1('pw')]]), \Base3\CoreFoundation\Api\IContainer::SHARED);
			$a=new \Base3\Core\Accesscontrol\Authentication\SingleAuth();
			$this->assertSame('singleauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			unset($_REQUEST['login']);
			$this->assertNull($a->login());
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}
