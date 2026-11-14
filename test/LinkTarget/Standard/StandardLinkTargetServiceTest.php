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

namespace Base3\Test\LinkTarget\Standard;

use PHPUnit\Framework\TestCase;

final class StandardLinkTargetServiceTest extends TestCase {

	public function testUsesQueryEncodingAndDefaultOutput(): void {
		$s = new \Base3\Core\LinkTarget\Standard\StandardLinkTargetService();
		$this->assertSame('?name=imprint&out=php', $s->getLink(['name' => 'imprint']));
		$this->assertSame('?name=hello+world&out=json&a=1', $s->getLink(['name'=>'hello world', 'out'=>'json'], ['a'=>1]));
	}

	public function testCustomParamsMayOverrideBaseTargetValues(): void {
		$s = new \Base3\Core\LinkTarget\Standard\StandardLinkTargetService();
		$this->assertSame('?name=other&out=xml', $s->getLink(['name'=>'old'], ['name'=>'other', 'out'=>'xml']));
	}

}
