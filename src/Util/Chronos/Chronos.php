<?php

namespace Util\Chronos;

class Chronos {

	// TODO diese Klasse von DateTime erben lassen?

	private $year = 1;
	private $month = 1;
	private $day = 1;
	private $hour = 0;
	private $minute = 0;
	private $second = 0;

	public static function create($year, $month, $day, $hour, $minute, $second) {
		$chronos = new self();
		$chronos->setYear($year)
			->setMonth($month)
			->setDay($day)
			->setHour($hour)
			->setMinute($minute)
			->setSecond($second);
		return $chronos;
	}

	public function format($format) {
		$d = new \DateTime();
		$d->setDate($this->year, $this->month, $this->day);
		$d->setTime($this->hour, $this->minute, $this->second);
		return $d->format($format);
	}

	private function initDate() {
		$d = new \DateTime();
		$d->setDate($this->year, $this->month, $this->day);
		$d->setTime($this->hour, $this->minute, $this->second);
		$this->year = intval($d->format("Y"));
		$this->month = intval($d->format("n"));
		$this->day = intval($d->format("j"));
		$this->hour = intval($d->format("G"));
		$this->minute = intval($d->format("i"));
		$this->second = intval($d->format("s"));
	}

	public function setYear($year) {
		if (is_int($year)) $this->year = $year;
		$this->initDate();
		return $this;
	}

	public function setMonth($month) {
		if (is_int($month)) $this->month = $month;
		$this->initDate();
		return $this;
	}

	public function setDay($day) {
		if (is_int($day)) $this->day = $day;
		$this->initDate();
		return $this;
	}

	public function setHour($hour) {
		if (is_int($hour)) $this->hour = $hour;
		$this->initDate();
		return $this;
	}

	public function setMinute($minute) {
		if (is_int($minute)) $this->minute = $minute;
		$this->initDate();
		return $this;
	}

	public function setSecond($second) {
		if (is_int($second)) $this->second = $second;
		$this->initDate();
		return $this;
	}

	public function addYears($years) {
		if (is_int($years)) $this->year += $years;
		$this->initDate();
		return $this;
	}

	public function addMonths($months) {
		if (is_int($months)) $this->month += $months;
		$this->initDate();
		return $this;
	}

	public function addDays($days) {
		if (is_int($days)) $this->day += $days;
		$this->initDate();
		return $this;
	}

	public function addHours($hours) {
		if (is_int($hours)) $this->hour += $hours;
		$this->initDate();
		return $this;
	}

	public function addMinutes($minutes) {
		if (is_int($minutes)) $this->minute += $minutes;
		$this->initDate();
		return $this;
	}

	public function addSeconds($seconds) {
		if (is_int($seconds)) $this->second += $seconds;
		$this->initDate();
		return $this;
	}

	public function getYear() {
		return intval($this->year);
	}

	public function getMonth() {
		return intval($this->month);
	}

	public function getDay() {
		return intval($this->day);
	}

	public function getHour() {
		return intval($this->hour);
	}

	public function getMinute() {
		return intval($this->minute);
	}

	public function getSecond() {
		return intval($this->second);
	}
}
