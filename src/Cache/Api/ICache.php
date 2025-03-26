<?php declare(strict_types=1);

namespace Base3\Cache\Api;

interface ICache {

	public function getCacheUrl($url, $refresh = false);
	public function getCacheUrls($urls, $refresh = false);

}
