<?php declare(strict_types=1);

namespace Crypt\Api;

interface ICrypt {

	public function encrypt(string $str, string $secret): string;
	public function decrypt(string $str, string $secret): string;

}

