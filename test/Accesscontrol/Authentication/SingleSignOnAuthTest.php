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

final class SingleSignOnAuthTest extends TestCase {

	public function testConsumesValidSingleSignOnTokenOnce(): void {
		$old=$_REQUEST;
		try {
			$token=new \Base3\Test\Accesscontrol\RecordingAuthToken();$token->valid=true;
			$a=new \Base3\Core\Accesscontrol\Authentication\SingleSignOnAuth($token);
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
