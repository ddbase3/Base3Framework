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

final class MultiAuthTest extends TestCase {

	public function testReadsConfiguredAccountsAndRejectsWrongPasswords(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();
			\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('configuration',new \Base3\Core\Configuration\ArrayConfiguration(['multiauth'=>['user'=>['first','second'],'pass'=>[sha1('pw1'),sha1('pw2')]]]), \Base3\CoreFoundation\Api\IContainer::SHARED);
			$a=new \Base3\Core\Accesscontrol\Authentication\MultiAuth();
			$this->assertSame('multiauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			$_REQUEST=['login'=>true,'username'=>'second','password'=>'wrong'];
			$this->assertNull($a->login());
			$_REQUEST['password']='pw2';
			$this->assertSame('second',$a->login());
			unset($_REQUEST['password']);
			$this->assertNull($a->login());
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}
