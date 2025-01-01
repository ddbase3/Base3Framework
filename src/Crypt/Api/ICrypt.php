<?php declare(strict_types=1);

namespace Crypt\Api;

interface ICrypt {

	public function encrypt($str, $secret);
	public function decrypt($str, $secret);

}

