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

namespace Base3\Test\Usermanager;

use Base3\CoreFoundation\Usermanager\User;
use Base3\Core\Usermanager\UsermanagerProxy;
use PHPUnit\Framework\TestCase;

final class UsermanagerProxySearchUsersTest extends TestCase {

	public function testSearchForwardsPaginationAndHydratesUserObjects(): void {
		$connector = new UsermanagerProxySearchUsersTestDouble1();

		$proxy = new UsermanagerProxy($connector);
		$result = $proxy->searchUsers(' Jane ', 12, 3);
		self::assertSame([['Jane', 12, 3]], $connector->calls);
		self::assertCount(1, $result);
		self::assertInstanceOf(User::class, $result[0]);
		self::assertSame('57', $result[0]->id);
		self::assertSame('jane', $result[0]->userid);
		$proxy->searchUsers('Jane', 12, 3);
		self::assertCount(2, $connector->calls); // No search-result cache.
	}

	public function testBlankSearchDoesNotCallConnector(): void {
		$connector = new UsermanagerProxySearchUsersTestDouble2();
		self::assertSame([], (new UsermanagerProxy($connector))->searchUsers('  '));
	}

	public function testInvalidPaginationIsRejectedWithoutConnectorCall(): void {
		$connector = new UsermanagerProxySearchUsersTestDouble3();
		$this->expectException(\InvalidArgumentException::class);
		(new UsermanagerProxy($connector))->searchUsers('test', 101);
	}

	public function testMalformedConnectorResultsAreNotAccepted(): void {
		$connector = new UsermanagerProxySearchUsersTestDouble4();
		$this->expectException(\UnexpectedValueException::class);
		(new UsermanagerProxy($connector))->searchUsers('any');
	}
}

require_once __DIR__ . '/UsermanagerProxySearchUsersTestDouble1.php';

require_once __DIR__ . '/UsermanagerProxySearchUsersTestDouble2.php';

require_once __DIR__ . '/UsermanagerProxySearchUsersTestDouble3.php';

require_once __DIR__ . '/UsermanagerProxySearchUsersTestDouble4.php';
