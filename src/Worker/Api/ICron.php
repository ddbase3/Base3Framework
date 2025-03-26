<?php declare(strict_types=1);

namespace Base3\Worker\Api;

interface ICron extends IJob {

	// array: ["0", "4", "*", "*", "*"]
	// Minute, Stunde, Tag des Monats, Monat, Wochentag
	public function getTimeCode();

}
