<?php

namespace Worker\Api;

interface ICron extends IJob {

	// array: ["0", "4", "*", "*", "*"]
	// Minute, Stunde, Tag des Monats, Monat, Wochentag
	public function getTimeCode();

}
