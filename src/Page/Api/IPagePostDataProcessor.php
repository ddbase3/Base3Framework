<?php declare(strict_types=1);

namespace Base3\Page\Api;

/**
 * Interface IPagePostDataProcessor
 *
 * Extends a page to support processing POST data and forwarding after submission.
 */
interface IPagePostDataProcessor extends IPage {

	/**
	 * Processes incoming POST data.
	 *
	 * Should be called when the page receives a POST request.
	 *
	 * @return void
	 */
	public function processPostData();

	/**
	 * Returns the URL to forward to after successful processing.
	 *
	 * @return string|null Forward target URL or null if no redirect is needed
	 */
	public function getForwardUrl();

}

