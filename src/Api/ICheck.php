<?php declare(strict_types=1);

namespace Base3\Api;

interface ICheck {

	/* for container services, to check if it's usable */
	public function checkDependencies();

}
