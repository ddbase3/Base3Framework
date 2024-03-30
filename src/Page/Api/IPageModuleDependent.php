<?php

namespace Page\Api;

use Api\IBase;

interface IPageModuleDependent extends IPageModule {

	public function getRequiredModules();

}
