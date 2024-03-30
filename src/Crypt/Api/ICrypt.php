<?php

namespace Crypt\Api;

interface ICrypt {

	public function encrypt($str, $secret);
	public function decrypt($str, $secret);

}

