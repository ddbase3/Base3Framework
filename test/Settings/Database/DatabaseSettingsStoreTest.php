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

namespace Base3\Test\Settings\Database;

use PHPUnit\Framework\TestCase;

final class DatabaseSettingsStoreTest extends TestCase {

	private function store(\Base3\Test\Support\RecordingDatabaseStub $db): \Base3\Core\Settings\Database\DatabaseSettingsStore {
		return new \Base3\Core\Settings\Database\DatabaseSettingsStore($db);
	}

	public function testInitializesTableAndReadsValidDataset(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$s=$this->store($db);
		$this->assertCount(1,$db->nonQueries);
		$this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `base3_settingsstore`',$db->nonQueries[0]);
		$db->singleRow=['settings'=>'{"enabled":true,"count":3}'];
		$this->assertSame(['enabled'=>true,'count'=>3],$s->get('group','name'));
		$this->assertSame(['enabled'=>true,'count'=>3],$s->get('group','name'));
		$this->assertCount(1,$db->nonQueries);
		$this->assertCount(2,$db->singleQueries);
		$this->assertTrue($db->connectCalls>=2);
	}

	public function testMissingAndMalformedSettingsDatasets(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$this->assertSame(['default'=>1],$s->get('g','n',['default'=>1]));
		$db->singleRow=['unexpected'=>null];
		$this->assertSame([],$s->get('g','n'));
		$db->singleRow=['settings'=>'not-json'];
		try { $s->get('g','n');$this->fail('Expected corrupt JSON exception'); }
		catch (\RuntimeException $e) { $this->assertStringContainsString('Failed to decode settings dataset',$e->getMessage()); }
		$db->singleRow=['settings'=>'"scalar"'];
		try { $s->get('g','n');$this->fail('Expected array validation'); }
		catch (\RuntimeException $e) { $this->assertStringContainsString('must decode to an array',$e->getMessage()); }
	}

	public function testWritesEscapesDeletesAndChecksExistence(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$s->set("g'1",'n',['path'=>'/a/b']);
		$this->assertCount(2,$db->nonQueries);
		$this->assertStringContainsString("g\\'1",$db->nonQueries[1]);
		$this->assertStringContainsString('ON DUPLICATE KEY UPDATE',$db->nonQueries[1]);
		$this->assertFalse($s->has('g','n'));
		$db->scalarValue=1;
		$this->assertTrue($s->has('g','n'));
		$s->remove('g','n');
		$this->assertStringContainsString('DELETE FROM',$db->nonQueries[2]);
		$s->save();
		$this->assertCount(3,$db->nonQueries);
	}

	public function testGroupLoadingAndInvalidRows(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$db->multiRows=[['name'=>'one','settings'=>'{"a":1}'],['name'=>'two','settings'=>'[]']];
		$this->assertSame(['one'=>['a'=>1],'two'=>[]],$s->getGroup('g'));
		$db->multiRows=[['name'=>'']];
		$this->expectException(\RuntimeException::class);
		$s->getGroup('g');
	}

	public function testRejectsInvalidNamesBeforeDatabaseWrites(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$this->expectException(\RuntimeException::class);
		$s->set('g','',['a'=>1]);
	}

	public function testRejectsInvalidJsonEncoding(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$this->expectException(\RuntimeException::class);
		$s->set('g','n',['infinite'=>INF]);
	}

	public function testDatabaseErrorIncludesOriginalDatabaseMessage(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$db->error=true;$db->errorText='read only';$db->errorCode=12;
		try { $s->has('g','n');$this->fail('Expected database error'); }
		catch (\RuntimeException $e) {
			$this->assertStringContainsString('Failed to check settings dataset existence.',$e->getMessage());
			$this->assertStringContainsString('[12] read only',$e->getMessage());
		}
	}

	public function testReloadRecreatesTableAndChecksConnection(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=$this->store($db);
		$s->reload();
		$this->assertCount(2,$db->nonQueries);
		$db->error=true;
		$this->expectException(\RuntimeException::class);
		$s->reload();
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
