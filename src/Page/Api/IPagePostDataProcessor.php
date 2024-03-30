<?php

namespace Page\Api;

interface IPagePostDataProcessor extends IPage {

	public function processPostData();
	public function getForwardUrl();

}
