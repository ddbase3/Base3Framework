<?php declare(strict_types=1);

namespace Base3\Page\Api;

/**
 * Interface IPageModuleFooter
 *
 * Represents a page module that appears in the footer area and defines a display priority.
 */
interface IPageModuleFooter extends IPageModule {

	/**
	 * Returns the display priority of the module.
	 *
	 * Lower values may appear earlier depending on layout logic.
	 *
	 * @return int Module priority
	 */
	public function getPriority();

}

