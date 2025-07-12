<?php declare(strict_types=1);

namespace Base3\Page\Api;

use Base3\Api\IBase;

/**
 * Interface IPageModuleDependent
 *
 * Represents a page module that declares dependencies on other modules.
 */
interface IPageModuleDependent extends IPageModule {

	/**
	 * Returns a list of required module names or identifiers.
	 *
	 * @return array<string> List of required module identifiers
	 */
	public function getRequiredModules();

}

