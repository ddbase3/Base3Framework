<?php declare(strict_types=1);

namespace Base3\Test\Session\NoSession;

use PHPUnit\Framework\TestCase;

final class NoSessionTest extends TestCase {

	public function testNeverStartsStoresOrExposesAnIdentity(): void {
		$s = new \Base3\Session\NoSession\NoSession();
		$this->assertFalse($s->started());
		$this->assertSame('', $s->getId());
		$this->assertFalse($s->start());
		$s->set('secret', 'value');
		$this->assertFalse($s->has('secret'));
		$this->assertSame('default', $s->get('secret', 'default'));
		$s->remove('secret');
		$this->assertFalse($s->destroy());
	}

}
