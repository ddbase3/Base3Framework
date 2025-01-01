<?php declare(strict_types=1);

namespace Page\Api;

use Api\IBase;

interface IPageModuleDependent extends IPageModule {

	public function getRequiredModules();

}
