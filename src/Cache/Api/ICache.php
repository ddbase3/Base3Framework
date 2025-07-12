<?php declare(strict_types=1);

namespace Base3\Cache\Api;

/**
 * Interface ICache
 *
 * Provides methods to retrieve cached versions of URLs, with optional refresh.
 */
interface ICache {

	/**
	 * Returns a cached version of the given URL.
	 *
	 * If $refresh is true, the cache may be forcibly updated.
	 *
	 * @param string $url The original URL to be cached
	 * @param bool $refresh Whether to force a cache refresh (default: false)
	 * @return string Cached URL
	 */
	public function getCacheUrl($url, $refresh = false);

	/**
	 * Returns cached versions of multiple URLs.
	 *
	 * If $refresh is true, the cache entries may be refreshed.
	 *
	 * @param array $urls Array of original URLs to be cached
	 * @param bool $refresh Whether to force a cache refresh (default: false)
	 * @return array Array of cached URLs (same order as input)
	 */
	public function getCacheUrls($urls, $refresh = false);

}

