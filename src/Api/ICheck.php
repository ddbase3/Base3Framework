<?php declare(strict_types=1);

namespace Api;

interface ICheck {

	/* for servicelocator services, to check if it's usable */
	public function checkDependencies();

}
