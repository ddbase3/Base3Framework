<?php declare(strict_types=1);

namespace Page\Api;

interface IPagePostDataProcessor extends IPage {

	public function processPostData();
	public function getForwardUrl();

}
