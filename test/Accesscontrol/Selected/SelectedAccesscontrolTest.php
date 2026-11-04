<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Selected;

use PHPUnit\Framework\TestCase;

final class SelectedAccesscontrolTest extends TestCase {

	public function testDeferredAuthenticationsResolveOnceAndShareTrustedIdentity(): void {
		$old=$_REQUEST;
		try {
			unset($_REQUEST['checkaccesscontrol']);
			$a=new \Base3\Test\Accesscontrol\RecordingAuthentication(null);
			$b=new \Base3\Test\Accesscontrol\RecordingAuthentication('member');
			$count=0;
			$s=new \Base3\Accesscontrol\Selected\SelectedAccesscontrol([$a,static function() use (&$count,$b) { $count++;return $b; }]);
			$this->assertSame(['authentication_methods_given'=>'Ok'],$s->checkDependencies());
			$this->assertSame('member',$s->getUserId());
			$this->assertSame('member',$s->getUserId());
			$this->assertSame(1,$count);
			$this->assertSame(['logout','login','keep:member','finish:member'],$a->lifecycle);
			$this->assertSame(['logout','login','keep:member','finish:member'],$b->lifecycle);
			$this->assertFalse($a->verbose);
		} finally { $_REQUEST=$old; }
	}

	public function testWithNoAuthenticationMethodsHasNoUser(): void {
		$s=new \Base3\Accesscontrol\Selected\SelectedAccesscontrol([]);
		$this->assertSame(['authentication_methods_given'=>'Fail'],$s->checkDependencies());
		$this->assertNull($s->getUserId());
	}

	public function testInvalidMethodIsRejectedBeforeAnyLogin(): void {
		$s=new \Base3\Accesscontrol\Selected\SelectedAccesscontrol([new \stdClass()]);
		$this->expectException(\InvalidArgumentException::class);
		$s->authenticate();
	}

	public function testLastNonNullActorIsSelected(): void {
		$old=$_REQUEST;
		try {
			unset($_REQUEST['checkaccesscontrol']);
			$a=new \Base3\Test\Accesscontrol\RecordingAuthentication('first');
			$b=new \Base3\Test\Accesscontrol\RecordingAuthentication('second');
			$s=new \Base3\Accesscontrol\Selected\SelectedAccesscontrol([$a,$b]);
			$this->assertSame('second',$s->getUserId());
			$this->assertSame('finish:second',$a->lifecycle[3]);
			$this->assertSame('finish:second',$b->lifecycle[3]);
		} finally { $_REQUEST=$old; }
	}

}

require_once __DIR__ . '/../RecordingAuthentication.php';
