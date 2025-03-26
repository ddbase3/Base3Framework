<?php declare(strict_types=1);

namespace Base3\Token\Api;

interface IToken {

	public function create($scope, $id, $size = 32, $duration = 3600);
	public function check($scope, $id, $token);
	public function delete($scope, $id, $token);
	public function clean($scope, $id);

}
