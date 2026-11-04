<?php declare(strict_types=1);

namespace Base3\Test\Language\MultiLang;

use PHPUnit\Framework\TestCase;

final class MultiLangTest extends TestCase {

	public function testChecksSessionAndAcceptsOnlyConfiguredLanguages(): void {
		$old = $_SESSION ?? null;
		try {
			$_SESSION = [];
			$l = new \Base3\Language\MultiLang\MultiLang(
				new \Base3\Configuration\ArrayConfiguration(['language' => ['main' => 'de', 'languages' => ['de', 'en']]]),
				new \Base3\Session\NoSession\NoSession()
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
