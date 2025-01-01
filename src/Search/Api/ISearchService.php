<?php declare(strict_types=1);

namespace Search\Api;

use Api\IBase;

interface ISearchService extends IBase {

	public function search($q);

}
