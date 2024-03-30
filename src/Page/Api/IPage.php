<?php

namespace Page\Api;

use Api\IOutput;

interface IPage extends IOutput {

	public function getUrl();

}

