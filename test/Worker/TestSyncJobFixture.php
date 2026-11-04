<?php declare(strict_types=1);

namespace Base3\Test\Worker;

use Base3\Worker\AbstractSyncJob;

/** Records the existing sync lifecycle without changing the job's scheduling or storage logic. */
final class TestSyncJobFixture extends AbstractSyncJob {
	public array $appEntries = [];
	public array $xrmEntries = [];
	public array $operations = [];

	public function getTimeCode() { return ['*', '*', '*', '*', '*']; }
	public function syncFile(): string { return $this->syncfile; }
	public function failCurrentSync(): void { $this->failed = true; }

	protected function getAppEntriesChange() { return $this->appEntries; }
	protected function getXrmEntriesChange() { return $this->xrmEntries; }

	protected function deleteFromAppDisconnect($id) { $this->operations[] = ['deleteFromAppDisconnect', $id]; }
	protected function deleteFromAppExecute($id) { $this->operations[] = ['deleteFromAppExecute', $id]; }
	protected function deleteFromAppConnect($id) { $this->operations[] = ['deleteFromAppConnect', $id]; }
	protected function deleteFromXrmDisconnect($id) { $this->operations[] = ['deleteFromXrmDisconnect', $id]; }
	protected function deleteFromXrmExecute($id) { $this->operations[] = ['deleteFromXrmExecute', $id]; }
	protected function deleteFromXrmConnect($id) { $this->operations[] = ['deleteFromXrmConnect', $id]; }
	protected function createAppToXrmDisconnect($id) { $this->operations[] = ['createAppToXrmDisconnect', $id]; }
	protected function createAppToXrmExecute($id) { $this->operations[] = ['createAppToXrmExecute', $id]; }
	protected function createAppToXrmConnect($id) { $this->operations[] = ['createAppToXrmConnect', $id]; }
	protected function createXrmToAppDisconnect($id) { $this->operations[] = ['createXrmToAppDisconnect', $id]; }
	protected function createXrmToAppExecute($id) { $this->operations[] = ['createXrmToAppExecute', $id]; }
	protected function createXrmToAppConnect($id) { $this->operations[] = ['createXrmToAppConnect', $id]; }
	protected function updateAppToXrmDisconnect($id) { $this->operations[] = ['updateAppToXrmDisconnect', $id]; }
	protected function updateAppToXrmExecute($id) { $this->operations[] = ['updateAppToXrmExecute', $id]; }
	protected function updateAppToXrmConnect($id) { $this->operations[] = ['updateAppToXrmConnect', $id]; }
	protected function updateXrmToAppDisconnect($id) { $this->operations[] = ['updateXrmToAppDisconnect', $id]; }
	protected function updateXrmToAppExecute($id) { $this->operations[] = ['updateXrmToAppExecute', $id]; }
	protected function updateXrmToAppConnect($id) { $this->operations[] = ['updateXrmToAppConnect', $id]; }
}
