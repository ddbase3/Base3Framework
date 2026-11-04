<?php declare(strict_types=1);

namespace Base3\Test\Configuration;

use Base3\Configuration\AbstractConfiguration;

/** Records storage boundary calls while exercising AbstractConfiguration itself. */
final class RecordingConfigurationFixture extends AbstractConfiguration {

	public array $storage;
	public array $savedSnapshots = [];
	public int $loadCalls = 0;
	public int $saveCalls = 0;
	public bool $saveSucceeds = true;

	public function __construct(array $initial = []) {
		$this->storage = $initial;
	}

	protected function load(): array {
		$this->loadCalls++;
		return $this->storage;
	}

	protected function saveData(array $data): bool {
		$this->saveCalls++;
		$this->savedSnapshots[] = $data;
		if (!$this->saveSucceeds) return false;
		$this->storage = $data;
		return true;
	}
}
