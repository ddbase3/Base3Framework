<?php declare(strict_types=1);

namespace File\Base;

use File\Api\IFileservice;

class BaseFileservice implements IFileservice {

	private function getFullPath($filename) {
		return DIR_USERFILES . ltrim($filename, DIRECTORY_SEPARATOR);
	}

	// Implementation of IFileservice

	public function getContents($filename, $base64 = false) {
		$content = file_get_contents($this->getFullPath($filename));
		return $base64 ? base64_encode($content) : $content;
	}

}
