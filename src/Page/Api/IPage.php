<?php declare(strict_types=1);

namespace Base3\Page\Api;

use Base3\Api\IOutput;

/**
 * Interface IPage
 *
 * Represents a renderable page that also provides a public URL.
 * Inherits output behavior from IOutput.
 */
interface IPage extends IOutput {

	/**
	 * Returns the public URL of the page.
	 *
	 * @return string|null The URL of the page, or null if not available
	 */
	public function getUrl();

}

