<?php declare(strict_types=1);

namespace Base3\Worker\Api;

use Base3\Api\IBase;

interface IJob extends IBase {

	// active?
	public function isActive();

	// value 0..100
	public function getPriority();

	// do work
	public function go();

}
