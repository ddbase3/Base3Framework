<?php declare(strict_types=1);

namespace Base3\Test\Xrm\Simple;

use Base3\Xrm\Simple\SimpleXrm;

/** Uses the original SimpleXrm database methods with controlled access and entry reads. */
final class SimpleXrmMutationFixture extends SimpleXrm {

	public mixed $currentEntry = null;
	public string $currentAccess = 'none';
	public array $lookups = [];

	public function getEntry($id) {
		$this->lookups[] = $id;
		return $this->currentEntry;
	}

	public function getAccess($entry) { return $this->currentAccess; }
}
