<?php declare(strict_types=1);

namespace Downloader\Base;

use Downloader\Api\IDownloader;

class BaseDownloader implements IDownloader {

	// Implementation of IDownloader

	public function download($url, $base64 = false) {
		$content = file_get_contents($url);
		return $base64 ? base64_encode($content) : $content;
	}

}
