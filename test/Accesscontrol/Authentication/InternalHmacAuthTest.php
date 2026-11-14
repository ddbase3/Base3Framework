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

final class InternalHmacAuthTest extends TestCase {

	public function testAuthenticatedHmacHeadersRequireValidTokenHashAndAge(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$server=$_SERVER;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('classmap',new \stdClass(), \Base3\CoreFoundation\Api\IContainer::SHARED);
			$new->set('configuration',new \Base3\Core\Configuration\ArrayConfiguration(['microservice'=>['masterpass'=>'topsecret']]), \Base3\CoreFoundation\Api\IContainer::SHARED);
			$token=new \Base3\Test\Accesscontrol\RecordingAuthToken();
			$new->set('authtoken',$token, \Base3\CoreFoundation\Api\IContainer::SHARED);
			$a=new \Base3\Core\Accesscontrol\Authentication\InternalHmacAuth();
			$this->assertSame('internalhmacauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			$t=time();
			$_SERVER['HTTP_USER']='internal';$_SERVER['HTTP_TIME']=(string)$t;
			$_SERVER['HTTP_TOKEN']='random-token';
			$_SERVER['HTTP_HASH']=sha1('topsecret'.$t.'random-token');
			$_SERVER['HTTP_AUTH']='trusted';
			$this->assertSame('trusted',$a->login());
			$this->assertCount(1,$token->saves);
			$token->valid=true;
			$this->assertNull($a->login());
			$token->valid=false;
			$_SERVER['HTTP_TIME']=(string)(time()-20000);
			$this->assertNull($a->login());
			$_SERVER['HTTP_TIME']=(string)$t;
			$_SERVER['HTTP_HASH']='invalid';
			$this->assertNull($a->login());
		} finally { $_SERVER=$server;\Base3\Core\ServiceLocator::useInstance($old); }
	}

	public function testIncompleteHeadersCannotAuthenticate(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$server=$_SERVER;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$new->set('configuration',new \Base3\Core\Configuration\ArrayConfiguration(['microservice'=>['masterpass'=>'pw']]), \Base3\CoreFoundation\Api\IContainer::SHARED);
			$a=new \Base3\Core\Accesscontrol\Authentication\InternalHmacAuth();
			unset($_SERVER['HTTP_USER'],$_SERVER['HTTP_TIME'],$_SERVER['HTTP_TOKEN'],$_SERVER['HTTP_HASH']);
			$this->assertNull($a->login());
		} finally { $_SERVER=$server;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}

require_once __DIR__ . '/../RecordingAuthToken.php';
