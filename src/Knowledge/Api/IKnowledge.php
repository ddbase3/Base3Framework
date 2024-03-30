<?php

namespace Knowledge\Api;

interface IKnowledge {

	public function getScopes();
	public function getFields($scope);
	public function getData($scope, $fields = null);

}
