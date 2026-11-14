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

namespace Base3\Test\Authorization\Dto;

use PHPUnit\Framework\TestCase;

final class AuthorizationSubjectTest extends TestCase {

	public function testCarriesDisplayMetadataWithoutGrantingRights(): void {
		$ref = new \Base3\CoreFoundation\Authorization\Dto\SubjectReference('host', 'user', 123);
		$subject = new \Base3\CoreFoundation\Authorization\Dto\AuthorizationSubject($ref, 'Person', false, ['department' => 'IT']);
		$this->assertSame($ref, $subject->reference);
		$this->assertSame('Person', $subject->label);
		$this->assertFalse($subject->editable);
		$this->assertSame('IT', $subject->metadata['department']);
	}

}
