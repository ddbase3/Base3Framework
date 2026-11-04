<?php declare(strict_types=1);

namespace Base3\Test\Util\Chronos;

use PHPUnit\Framework\TestCase;

final class ChronosTimeTest extends TestCase {

	public function testCalendarArithmeticAcrossLeapDayAndMidnight(): void {
		$c=\Base3\Util\Chronos\Chronos::create(2024,2,28,23,59,30);
		$this->assertSame('2024-02-28 23:59:30',$c->format('Y-m-d H:i:s'));
		$c->addSeconds(90);
		$this->assertSame('2024-02-29 00:01:00',$c->format('Y-m-d H:i:s'));
		$c->addDays(2);
		$this->assertSame('2024-03-02',$c->format('Y-m-d'));
		$this->assertSame(2024,$c->getYear());
		$this->assertSame(3,$c->getMonth());
		$this->assertSame(2,$c->getDay());
	}

	public function testInvalidSetterTypesAreIgnoredAndValidValuesNormalize(): void {
		$c=\Base3\Util\Chronos\Chronos::create(2024,1,1,12,15,25);
		$c->setYear('1999')->setMonth('4')->setDay('9')->setHour('6')->setMinute('7')->setSecond('8');
		$this->assertSame('2024-01-01 12:15:25',$c->format('Y-m-d H:i:s'));
		$c->addHours(13);
		$this->assertSame('2024-01-02 01:15:25',$c->format('Y-m-d H:i:s'));
		$c->addMinutes(45);
		$this->assertSame(2,$c->getHour());
		$this->assertSame(0,$c->getMinute());
	}

}
