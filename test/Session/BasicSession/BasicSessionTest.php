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

namespace Base3\Test\Session\BasicSession;

use PHPUnit\Framework\TestCase;

final class BasicSessionTest extends TestCase {

	public function testCliSessionRemainsInactiveWithoutMutatingGlobalSession(): void {
		$session=$_SESSION ?? [];
		try {
			$_SESSION=['keep'=>'unchanged'];
			$s=new \Base3\Core\Session\BasicSession\BasicSession(new \Base3\Core\Configuration\ArrayConfiguration());
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
