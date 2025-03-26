<?php declare(strict_types=1);

namespace Base3\Page\Api;

use Base3\Api\IBase;

interface IPageModule extends IBase {

	public function setData($data);
	public function getHtml();

}
