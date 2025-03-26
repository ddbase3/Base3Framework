<?php declare(strict_types=1);

namespace Base3\Page\Api;

use Base3\Api\IOutput;

interface IPage extends IOutput {

	public function getUrl();

}

