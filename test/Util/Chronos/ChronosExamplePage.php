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

use Base3\CoreFoundation\Page\Api\IPage;
use Base3\CoreFoundation\Util\Chronos\Chronos;

class ChronosExamplePage implements IPage {

	// Implementation of IBase

	public static function getName(): string {
		return "chronosexamplepage";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {
		$str = '';//'<h1>ChronosTest</h1>';

		$tl = date("Y-m-d H:i:s");
		$tc = [30, 4, "*", "*", "*"];
		$str .= $tl . "<br />" . $this->getNextExecution($tl, $tc);

		return $str;
	}

	public function getHelp(): string {
		return 'Help of ChronosExamplePage' . "\n";
	}

	// Private methods

	private function getNextExecution($tl, $tc) {
		$tx = str_replace([" ", ":"], "-", $tl);
		$td = array_map("intval", explode("-", $tx));

		$d = Chronos::create($td[0], $td[1], $td[2], $td[3], $td[4], $td[5]);

		// seconds
		if ($d->getSecond() != 0) {
			$d->addMinutes(1);
			$d->setSecond(0);
		}		

		// minutes
		if (is_int($tc[0])) {  // ganzzahlig
			if ($d->getMinute() > $tc[0]) $d->addHours(1);
			$d->setMinute($tc[0]);
		}

		// hours
		if (is_int($tc[1])) {  // ganzzahlig
			if ($d->getHour() > $tc[1]) $d->addDays(1);
			$d->setHour($tc[1]);
		}

		// days
		if (is_int($tc[2])) {  // ganzzahlig
			if ($d->getDay() > $tc[2]) $d->addMonth(1);
			$d->setDay($tc[2]);
		}

		// months
		if (is_int($tc[3])) {  // ganzzahlig
			if ($d->getMonth() > $tc[3]) $d->addYear(1);
			$d->setMonth($tc[3]);
		}

		// TODO Wochentage
		// TODO Aufzählungen, Bruch-Angaben

		return $d->format("Y-m-d H:i:s");
	}

}
