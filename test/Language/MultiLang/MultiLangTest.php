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

namespace Base3\Test\Language\MultiLang;

use PHPUnit\Framework\TestCase;

final class MultiLangTest extends TestCase {

	public function testChecksSessionAndAcceptsOnlyConfiguredLanguages(): void {
		$old = $_SESSION ?? null;
		try {
			$_SESSION = [];
			$l = new \Base3\Core\Language\MultiLang\MultiLang(
				new \Base3\Core\Configuration\ArrayConfiguration(['language' => ['main' => 'de', 'languages' => ['de', 'en']]]),
				new \Base3\Core\Session\NoSession\NoSession()
			);
			$this->assertSame('de', $l->getLanguage());
			$this->assertSame(['de', 'en'], $l->getLanguages());
			$l->setLanguage('xx');
			$this->assertSame('de', $l->getLanguage());
			$l->setLanguage('en');
			$this->assertSame('en', $l->getLanguage());
			$this->assertSame('en', $_SESSION['language']);
			$this->assertSame(['session_started' => 'Fail'], $l->checkDependencies());
		} finally {
			if ($old === null) { unset($_SESSION); } else { $_SESSION = $old; }
		}
	}

}
