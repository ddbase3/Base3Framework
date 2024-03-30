<?php

namespace Configuration\Api;

interface IConfiguration {

	public function get($configuration = "");
	public function set($data, $configuration = "");
	public function save();

}
