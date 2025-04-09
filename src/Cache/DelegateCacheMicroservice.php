<?php declare(strict_types=1);

namespace Base3\Cache;

use Base3\Core\ServiceLocator;
use Base3\Cache\Api\ICache;
use Base3\Microservice\AbstractMicroservice;

class DelegateCacheMicroservice extends AbstractMicroservice implements ICache {

	private $servicelocator;
	private $cache;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->cache = $this->servicelocator->get('cache');
	}

	// Implementation of ICache

	public function getCacheUrl($url, $refresh = false) {
		return $this->cache->getCacheUrl($url, $refresh);
	}

	public function getCacheUrls($urls, $refresh = false) {
		return $this->cache->getCacheUrl($urls, $refresh);
	}

}
