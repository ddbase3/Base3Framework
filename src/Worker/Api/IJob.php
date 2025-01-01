<?php declare(strict_types=1);

namespace Worker\Api;

use Api\IBase;

interface IJob extends IBase {

	// active?
	public function isActive();

	// value 0..100
	public function getPriority();

	// do work
	public function go();

}
