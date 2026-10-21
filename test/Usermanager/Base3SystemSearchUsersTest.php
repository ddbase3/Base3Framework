<?php declare(strict_types=1);

namespace Base3\Test\Usermanager;

use Base3\Api\IContainer;
use Base3\Core\ServiceLocator;
use Base3\Database\Api\IDatabase;
use Base3\Usermanager\Base3System\Base3SystemUsermanager;
use Base3\Usermanager\User;
use PHPUnit\Framework\TestCase;

final class Base3SystemSearchUsersTest extends TestCase {

	protected function tearDown(): void {
		// The standalone manager uses the framework's existing service locator.
		$property = (new \ReflectionClass(ServiceLocator::class))->getProperty('externalInstance');
		$property->setValue(null, null);
	}

	public function testSearchUsesOneNativePagedQueryWithoutPerUserRoleLookups(): void {
		$database = $this->createMock(IDatabase::class);
		$database->expects(self::once())->method('connect');
		$database->expects(self::once())->method('escape')->with('%Müller%')->willReturn('%Müller%');
		$database->expects(self::never())->method('singleQuery');

		$rows = [
			['id' => 7, 'userid' => 'am', 'fullname' => 'Anna Müller', 'email' => 'anna@example.org', 'mode' => 1, 'lang' => 'de'],
			['id' => 9, 'userid' => 'mm', 'fullname' => 'Max Müller', 'email' => 'max@example.org', 'mode' => 2, 'lang' => 'en']
		];
		$database->expects(self::once())->method('multiQuery')
			->with(self::callback(static function (string $sql): bool {
				return str_contains($sql, 'LOWER(u.`name`) LIKE LOWER(\'%Müller%\')')
					&& str_contains($sql, 'LOWER(u.`fullname`) LIKE LOWER(\'%Müller%\')')
					&& str_contains($sql, 'LOWER(u.`email`) LIKE LOWER(\'%Müller%\')')
					&& str_contains($sql, 'u.`id` != 1')
					&& str_contains($sql, 'ORDER BY u.`fullname`, u.`id`')
					&& str_contains($sql, 'LIMIT 2 OFFSET 4');
			}))
			->willReturn($rows);

		$manager = $this->manager($database);
		$result = $manager->searchUsers(' Müller ', 2, 4);
		self::assertCount(2, $result);
		self::assertContainsOnlyInstancesOf(User::class, $result);
		self::assertSame('am', $result[0]->userid);
		self::assertSame('Anna Müller', $result[0]->name);
		self::assertSame([], $result[0]->roles);
		self::assertNull($result[0]->role);
		self::assertNull($result[1]->role);
	}

	public function testSqlWildcardsAreLiteralAndNumericQueriesMatchExactUserId(): void {
		$database = $this->createMock(IDatabase::class);
		$database->expects(self::once())->method('escape')->with('%4!%!_!!2%')->willReturn('%4!%!_!!2%');
		$database->expects(self::once())->method('multiQuery')
			->with(self::callback(static fn(string $sql): bool =>
				str_contains($sql, "ESCAPE '!'") && !str_contains($sql, 'u.`id` = ')
			))
			->willReturn([]);
		self::assertSame([], $this->manager($database)->searchUsers('4%_!2'));

		$database = $this->createMock(IDatabase::class);
		$database->expects(self::once())->method('escape')->with('%42%')->willReturn('%42%');
		$database->expects(self::once())->method('multiQuery')
			->with(self::callback(static fn(string $sql): bool => str_contains($sql, 'u.`id` = 42')))
			->willReturn([]);
		self::assertSame([], $this->manager($database)->searchUsers('42'));
	}

	public function testExistingIndividualLookupStillResolvesRoleMetadata(): void {
		$database = $this->createMock(IDatabase::class);
		$database->expects(self::once())->method('singleQuery')->willReturn([
			'id' => 7, 'userid' => 'am', 'fullname' => 'Anna Müller',
			'email' => 'anna@example.org', 'lang' => 'de', 'mode' => 1
		]);
		$database->expects(self::once())->method('multiQuery')
			->with(self::callback(static fn(string $sql): bool => str_contains($sql, 'base3system_sysuserrole')))
			->willReturn([['id' => 4, 'name' => 'admin']]);

		$user = $this->manager($database)->getUserById(7);
		self::assertInstanceOf(User::class, $user);
		self::assertSame('Anna Müller', $user->name);
		self::assertSame('admin', $user->role);
		self::assertCount(1, $user->roles);
	}

	public function testBlankQueryDoesNotTouchBackend(): void {
		$database = $this->createMock(IDatabase::class);
		$database->expects(self::never())->method('connect');
		$database->expects(self::never())->method('multiQuery');
		self::assertSame([], $this->manager($database)->searchUsers("  \t  "));
	}

	public function testInvalidPaginationIsRejectedBeforeDatabaseAccess(): void {
		$database = $this->createMock(IDatabase::class);
		$database->expects(self::never())->method('connect');
		$manager = $this->manager($database);
		foreach ([[0, 0], [101, 0], [20, -1]] as [$limit, $offset]) {
			try {
				$manager->searchUsers('anna', $limit, $offset);
				self::fail('Invalid pagination must throw.');
			} catch (\InvalidArgumentException $exception) {
				self::assertNotSame('', $exception->getMessage());
			}
		}
	}

	private function manager(IDatabase $database): Base3SystemUsermanager {
		$locator = new ServiceLocator();
		$locator->set('database', $database, IContainer::SHARED);
		ServiceLocator::useInstance($locator);
		return new Base3SystemUsermanager();
	}
}
