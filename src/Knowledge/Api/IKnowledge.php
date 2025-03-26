<?php declare(strict_types=1);

namespace Base3\Knowledge\Api;

interface IKnowledge {

	public function getScopes();
	public function getFields($scope);
	public function getData($scope, $fields = null);

}
