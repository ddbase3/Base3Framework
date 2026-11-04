<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Cache;

use Base3\Usermanager\No\NoUsermanager;

final class CacheUsermanagerFixture extends NoUsermanager {

	public function __construct(
		public array $currentUser = ['id'=>'alice','role'=>'reader'],
		public array $currentGroups = []
	) {}

	public function getUser() { return $this->currentUser; }
	public function getGroups() { return $this->currentGroups; }
}
