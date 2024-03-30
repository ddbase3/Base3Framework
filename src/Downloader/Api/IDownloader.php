<?php

namespace Downloader\Api;

interface IDownloader {

	public function download($url, $base64 = false);

}
