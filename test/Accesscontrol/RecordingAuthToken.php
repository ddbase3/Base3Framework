<?php declare(strict_types=1);

namespace Base3\Test\Accesscontrol;

use Base3\Token\Api\IToken;

final class RecordingAuthToken implements IToken {
	public bool $valid = false;
	public array $saves = [];
	public array $deleted = [];
	public function create($scope,$id,$size=32,$duration=3600) { return 'created-token'; }
	public function check($scope,$id,$token) { return $this->valid; }
	public function delete($scope,$id,$token) { $this->deleted[]=[$scope,$id,$token]; }
	public function clean($scope,$id) {}
	public function save($scope,$id,$token,$duration) { $this->saves[]=[$scope,$id,$token,$duration]; }
}
