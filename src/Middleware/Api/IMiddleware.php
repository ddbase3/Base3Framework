<?php declare(strict_types=1);

namespace Middleware\Api;

interface IMiddleware {

	public function setNext($next);

	public function process();

}
