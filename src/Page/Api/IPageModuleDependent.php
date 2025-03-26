<?php declare(strict_types=1);

namespace Base3\Page\Api;

use Base3\Api\IBase;

interface IPageModuleDependent extends IPageModule {

	public function getRequiredModules();

}
