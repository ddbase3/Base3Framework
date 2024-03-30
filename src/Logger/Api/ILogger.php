<?php

namespace Logger\Api;

interface ILogger {

	public function log($scope, $log, $timestamp = null);
	public function getScopes();
	public function getNumOfScopes();
	public function getLogs($scope, $num = 50, $reverse = true);

}
