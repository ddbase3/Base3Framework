<?php declare(strict_types=1);

namespace Page\Api;

use Api\IBase;

interface IPageModule extends IBase {

	public function setData($data);
	public function getHtml();

}
