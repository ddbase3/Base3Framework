<?php declare(strict_types=1);

namespace Base3\Middleware\Api;

interface IMiddleware {

	public function setNext($next);

	public function process();

}
