<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Usermanager\User;
use Base3\Usermanager\UsermanagerProxy;
use PHPUnit\Framework\TestCase;

final class UsermanagerProxySearchUsersTest extends TestCase {

	public function testSearchForwardsPaginationAndHydratesUserObjects(): void {
		$connector = new class {
			public array $calls = [];

			public function searchUsers(string $query, int $limit, int $offset): array {
				$this->calls[] = [$query, $limit, $offset];
				return [['id' => '57', 'userid' => 'jane', 'name' => 'Jane']];
			}
		};

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
		$connector = new class {
			public function searchUsers(): array { throw new \RuntimeException('must not be called'); }
		};
		self::assertSame([], (new UsermanagerProxy($connector))->searchUsers('  '));
	}

	public function testInvalidPaginationIsRejectedWithoutConnectorCall(): void {
		$connector = new class {
			public function searchUsers(): array { throw new \RuntimeException('must not be called'); }
		};
		$this->expectException(\InvalidArgumentException::class);
		(new UsermanagerProxy($connector))->searchUsers('test', 101);
	}

	public function testMalformedConnectorResultsAreNotAccepted(): void {
		$connector = new class {
			public function searchUsers(): array { return [['id' => 123], 'not a user']; }
		};
		$this->expectException(\UnexpectedValueException::class);
		(new UsermanagerProxy($connector))->searchUsers('any');
	}
}
