<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Worker;

use Base3\CoreFoundation\Worker\AbstractSyncJob;

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
