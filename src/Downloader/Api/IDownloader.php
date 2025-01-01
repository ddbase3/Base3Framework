<?php declare(strict_types=1);

namespace Downloader\Api;

interface IDownloader {

	public function download($url, $base64 = false);

}
