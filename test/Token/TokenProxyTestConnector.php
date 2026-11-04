<?php declare(strict_types=1);

namespace Base3\Test\Token;

final class TokenProxyTestConnector {

	public array $calls = [];

	public function create($scope, $id, $size = 32, $duration = 3600): string {
		$this->calls[] = ['create',$scope,$id,$size,$duration];
		return 'token';
	}
	public function check($scope, $id, $token): bool {
		$this->calls[] = ['check',$scope,$id,$token];
		return $token === 'token';
	}
	public function delete($scope, $id, $token): void { $this->calls[] = ['delete',$scope,$id,$token]; }
	public function clean($scope, $id): void { $this->calls[] = ['clean',$scope,$id]; }
}
