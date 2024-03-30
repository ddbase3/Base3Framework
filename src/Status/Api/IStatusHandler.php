<?php

namespace Status\Api;

interface IStatusHandler {

	// $fields = null: get all
	// $fields = string: get special status
	// $fields = array of strings: get multiple status fields
	public function get($fields = null);

	// $data = array of key => values: set status fields
	public function set($data);

}
