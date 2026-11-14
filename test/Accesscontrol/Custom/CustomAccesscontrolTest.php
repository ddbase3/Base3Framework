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

namespace Base3\Test\Accesscontrol\Custom;

use PHPUnit\Framework\TestCase;

final class CustomAccesscontrolTest extends TestCase {

	public function testDiscoversExistingAuthenticationWithoutRepeatingLifecycle(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			unset($_REQUEST['checkaccesscontrol']);
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$map=new \Base3\Test\Support\StaticClassMapStub();
			$a=new \Base3\Test\Accesscontrol\RecordingAuthentication('member');
			$map->register('recordingauthentication',$a);
			$new->set('classmap',$map, \Base3\CoreFoundation\Api\IContainer::SHARED);
			$s=new \Base3\Core\Accesscontrol\Custom\CustomAccesscontrol();
			$this->assertSame(['depending_services'=>'Ok'],$s->checkDependencies());
			$this->assertNull($s->getUserId());
			$s->authenticate();
			$this->assertSame('member',$s->getUserId());
			$this->assertSame(['logout','login','keep:member','finish:member'],$a->lifecycle);
			$s->authenticate();
			$this->assertCount(4,$a->lifecycle);
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}

require_once __DIR__ . '/../RecordingAuthentication.php';
require_once __DIR__ . '/../../Support/StaticClassMapStub.php';
