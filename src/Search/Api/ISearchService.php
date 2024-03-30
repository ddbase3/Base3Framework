<?php

namespace Search\Api;

use Api\IBase;

interface ISearchService extends IBase {

	public function search($q);

}
