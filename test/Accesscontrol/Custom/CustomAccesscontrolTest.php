<?php declare(strict_types=1);

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
			$new->set('classmap',$map, \Base3\Api\IContainer::SHARED);
			$s=new \Base3\Accesscontrol\Custom\CustomAccesscontrol();
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
