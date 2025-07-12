<?php declare(strict_types=1);

namespace Base3\Page\Api;

/**
 * Interface IPageModuleHeader
 *
 * Represents a page module that appears in the header area and defines a display priority.
 */
interface IPageModuleHeader extends IPageModule {

	/**
	 * Returns the display priority of the module.
	 *
	 * Higher values typically appear earlier (e.g. top or left).
	 *
	 * @return int Module priority
	 */
	public function getPriority();

}

