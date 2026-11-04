<?php declare(strict_types=1);

namespace Base3\Test\Cache;

use Base3\Cache\Api\ICache;

final class RecordingCacheFixture implements ICache {

	public array $calls=[];
	public function getCacheUrl($url, $refresh=false) {
		$this->calls[]=['one',$url,$refresh];
		return 'cached-url';
	}
	public function getCacheUrls($urls, $refresh=false) {
		$this->calls[]=['many',$urls,$refresh];
		return ['cached-url'];
	}
}
