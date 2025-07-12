<?php declare(strict_types=1);

namespace Base3\Page\Api;

use Base3\Api\IBase;

/**
 * Interface IPageModule
 *
 * Defines a reusable page module that can receive data and render HTML.
 */
interface IPageModule extends IBase {

	/**
	 * Sets the data used by the module.
	 *
	 * @param mixed $data Arbitrary data to be used in rendering (e.g. array, object)
	 * @return void
	 */
	public function setData($data);

	/**
	 * Returns the rendered HTML of the module.
	 *
	 * @return string HTML output
	 */
	public function getHtml();

}

