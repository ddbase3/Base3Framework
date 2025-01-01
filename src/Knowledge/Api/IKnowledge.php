<?php declare(strict_types=1);

namespace Knowledge\Api;

interface IKnowledge {

	public function getScopes();
	public function getFields($scope);
	public function getData($scope, $fields = null);

}
