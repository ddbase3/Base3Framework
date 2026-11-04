<?php declare(strict_types=1);

namespace Base3\Test\Session\BasicSession;

use PHPUnit\Framework\TestCase;

final class BasicSessionTest extends TestCase {

	public function testCliSessionRemainsInactiveWithoutMutatingGlobalSession(): void {
		$session=$_SESSION ?? [];
		try {
			$_SESSION=['keep'=>'unchanged'];
			$s=new \Base3\Session\BasicSession\BasicSession(new \Base3\Configuration\ArrayConfiguration());
			$this->assertFalse($s->started());
			$this->assertFalse($s->start());
			$this->assertSame('',$s->getId());
			$this->assertSame('fallback',$s->get('missing','fallback'));
			$this->assertFalse($s->has('keep'));
			$s->set('new','value');
			$s->remove('keep');
			$this->assertSame(['keep'=>'unchanged'],$_SESSION);
			$this->assertFalse($s->destroy());
		} finally { $_SESSION=$session; }
	}

}
