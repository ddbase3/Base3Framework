<?php

namespace Middleware\Api;

interface IMiddleware {

	public function setNext($next);

	public function process();

}
