<?php declare(strict_types=1);

namespace Base3\Api;

interface ICheck {

	/* for servicelocator services, to check if it's usable */
	public function checkDependencies();

}
