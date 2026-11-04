<?php declare(strict_types=1);

namespace Base3\Test\Util\Chronos;

use Base3\Util\Chronos\Chronos;
use PHPUnit\Framework\TestCase;

final class ChronosCalendarCoverageTest extends TestCase {

	public function testYearMonthAndDayIncrementsNormalizeLeapDay(): void {
		$date = Chronos::create(2024, 2, 29, 12, 0, 0);
		$this->assertSame($date, $date->addYears(1));
		$this->assertSame('2025-03-01', $date->format('Y-m-d'));
		$date->addMonths(10);
		$this->assertSame('2026-01-01', $date->format('Y-m-d'));
		$date->addDays(31);
		$this->assertSame('2026-02-01', $date->format('Y-m-d'));
	}

	public function testNegativeTimeArithmeticCrossesYearBoundary(): void {
		$date = Chronos::create(2025, 1, 1, 0, 0, 0);
		$date->addSeconds(-1);
		$this->assertSame('2024-12-31 23:59:59', $date->format('Y-m-d H:i:s'));
		$date->addMinutes(-59)->addHours(-23);
		$this->assertSame('2024-12-31 00:00:59', $date->format('Y-m-d H:i:s'));
	}

	public function testValidSettersReturnSameInstanceAndNormalizeOverflow(): void {
		$date = Chronos::create(2025, 1, 1, 0, 0, 0);
		$this->assertSame($date, $date->setYear(2023));
		$this->assertSame($date, $date->setMonth(12));
		$this->assertSame($date, $date->setDay(31));
		$this->assertSame($date, $date->setHour(23));
		$this->assertSame($date, $date->setMinute(59));
		$this->assertSame($date, $date->setSecond(59));
		$this->assertSame('2023-12-31 23:59:59', $date->format('Y-m-d H:i:s'));
	}

	public function testInvalidArithmeticTypesDoNotChangeClock(): void {
		$date = Chronos::create(2024, 6, 15, 12, 30, 45);
		$date->addYears('1')->addMonths('1')->addDays('1');
		$date->addHours('1')->addMinutes('1')->addSeconds('1');
		$this->assertSame('2024-06-15 12:30:45', $date->format('Y-m-d H:i:s'));
	}

	public function testAllDatePartsAreAvailableAfterNormalization(): void {
		$date = Chronos::create(2024, 2, 29, 23, 59, 58);
		$date->addSeconds(2);
		$this->assertSame(2024, $date->getYear());
		$this->assertSame(3, $date->getMonth());
		$this->assertSame(1, $date->getDay());
		$this->assertSame(0, $date->getHour());
		$this->assertSame(0, $date->getMinute());
		$this->assertSame(0, $date->getSecond());
	}

	public function testConstructorDefaultsAreNormalizedByFormatter(): void {
		$date = new Chronos();
		$this->assertSame('0001-01-01 00:00:00', $date->format('Y-m-d H:i:s'));
	}
}
