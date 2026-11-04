<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class SingleSignOnAuthTest extends TestCase {

	public function testConsumesValidSingleSignOnTokenOnce(): void {
		$old=$_REQUEST;
		try {
			$token=new \Base3\Test\Accesscontrol\RecordingAuthToken();$token->valid=true;
			$a=new \Base3\Accesscontrol\Authentication\SingleSignOnAuth($token);
			$this->assertSame('singlesignonauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			$_REQUEST=['userid'=>'member','ssocode'=>'secret'];
			$this->assertSame('member',$a->login());
			$this->assertSame([['singlesignon','member','secret']],$token->deleted);
			$token->valid=false;
			$this->assertNull($a->login());
			$this->assertCount(1,$token->deleted);
			unset($_REQUEST['ssocode']);
			$this->assertNull($a->login());
		} finally { $_REQUEST=$old; }
	}

}

require_once __DIR__ . '/../RecordingAuthToken.php';
