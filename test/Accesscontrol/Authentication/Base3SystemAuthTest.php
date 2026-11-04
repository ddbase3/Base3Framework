<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol\Authentication;

use PHPUnit\Framework\TestCase;

final class Base3SystemAuthTest extends TestCase {

	public function testAuthenticatesVerifiedRecordAndEscapesLoginName(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$db=new \Base3\Test\Support\RecordingDatabaseStub();
			$db->singleRow=['name'=>'maria','mode'=>1];
			$new->set('database',$db, \Base3\Api\IContainer::SHARED);
			$a=new \Base3\Accesscontrol\Authentication\Base3SystemAuth();
			$this->assertSame('base3systemauth',$a::getName());
			$this->assertSame(['depending_services'=>'Ok'],$a->checkDependencies());
			$_REQUEST=['login'=>true,'username'=>"ma'ria",'password'=>'s3cr3t'];
			$this->assertSame('maria',$a->login());
			$this->assertStringContainsString("ma\\'ria",$db->singleQueries[0]);
			$this->assertStringContainsString(md5('s3cr3t'),$db->singleQueries[0]);
			$db->singleRow=null;
			$this->assertNull($a->login());
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

	public function testSwitchIdentityRequiresPrivilegedRecordMode(): void {
		$old=\Base3\Core\ServiceLocator::getInstance();$request=$_REQUEST;
		try {
			$new=new \Base3\Core\ServiceLocator();\Base3\Core\ServiceLocator::useInstance($new);
			$db=new \Base3\Test\Support\RecordingDatabaseStub();
			$new->set('database',$db, \Base3\Api\IContainer::SHARED);
			$a=new \Base3\Accesscontrol\Authentication\Base3SystemAuth();
			$_REQUEST=['login'=>true,'username'=>'admin','password'=>'pw','switchuser'=>'other'];
			$db->singleRow=['name'=>'admin','mode'=>1];
			$this->assertSame('admin',$a->login());
			$db->singleRow=['name'=>'admin','mode'=>2];
			$this->assertSame('other',$a->login());
		} finally { $_REQUEST=$request;\Base3\Core\ServiceLocator::useInstance($old); }
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
