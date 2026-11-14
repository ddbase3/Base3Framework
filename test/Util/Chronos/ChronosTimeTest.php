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

namespace Base3\Test\Util\Chronos;

use PHPUnit\Framework\TestCase;

final class ChronosTimeTest extends TestCase {

	public function testCalendarArithmeticAcrossLeapDayAndMidnight(): void {
		$c=\Base3\CoreFoundation\Util\Chronos\Chronos::create(2024,2,28,23,59,30);
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
		$c=\Base3\CoreFoundation\Util\Chronos\Chronos::create(2024,1,1,12,15,25);
		$c->setYear('1999')->setMonth('4')->setDay('9')->setHour('6')->setMinute('7')->setSecond('8');
		$this->assertSame('2024-01-01 12:15:25',$c->format('Y-m-d H:i:s'));
		$c->addHours(13);
		$this->assertSame('2024-01-02 01:15:25',$c->format('Y-m-d H:i:s'));
		$c->addMinutes(45);
		$this->assertSame(2,$c->getHour());
		$this->assertSame(0,$c->getMinute());
	}

}
