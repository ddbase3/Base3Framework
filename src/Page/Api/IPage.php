<?php declare(strict_types=1);

namespace Page\Api;

use Api\IOutput;

interface IPage extends IOutput {

	public function getUrl();

}

