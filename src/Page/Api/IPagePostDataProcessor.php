<?php declare(strict_types=1);

namespace Base3\Page\Api;

interface IPagePostDataProcessor extends IPage {

	public function processPostData();
	public function getForwardUrl();

}
