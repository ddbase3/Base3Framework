<?php

namespace Cache;

use Cache\Api\ICache;
use Microservice\AbstractMicroservice;

class DelegateCacheMicroservice extends AbstractMicroservice implements ICache {

	private $cache;

	public function __construct($cnf = null) {
		$this->servicelocator = \Base3\ServiceLocator::getInstance();
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
