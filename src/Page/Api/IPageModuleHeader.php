<?php declare(strict_types=1);

namespace Page\Api;

interface IPageModuleHeader extends IPageModule {

	public function getPriority();

}
