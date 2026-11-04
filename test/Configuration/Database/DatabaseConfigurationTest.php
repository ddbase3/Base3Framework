<?php declare(strict_types=1);

namespace Base3\Test\Configuration\Database;

use PHPUnit\Framework\TestCase;

final class DatabaseConfigurationTest extends TestCase {

	public function testLoadsAndDecodesSupportedDatabaseTypes(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$db->multiRows=[
			['group'=>'base','name'=>'text','type'=>'string','value'=>'hey'],
			['group'=>'base','name'=>'number','type'=>'int','value'=>'17'],
			['group'=>'base','name'=>'decimal','type'=>'float','value'=>'1.25'],
			['group'=>'base','name'=>'yes','type'=>'bool','value'=>' YES '],
			['group'=>'base','name'=>'no','type'=>'bool','value'=>'off'],
			['group'=>'base','name'=>'items','type'=>'array','value'=>'{"a":1}'],
			['group'=>'base','name'=>'empty','type'=>'array','value'=>''],
			['group'=>'base','name'=>'nil','type'=>'null','value'=>''],
		];
		$s=new \Base3\Configuration\Database\DatabaseConfiguration($db);
		$this->assertSame(['text'=>'hey','number'=>17,'decimal'=>1.25,'yes'=>true,'no'=>false,'items'=>['a'=>1],'empty'=>[],'nil'=>null],$s->get('base'));
		$this->assertCount(1,$db->nonQueries);
		$this->assertSame(1,count($db->multiQueries));
	}

	public function testPersistsScalarAndStructuredValues(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=new \Base3\Configuration\Database\DatabaseConfiguration($db);
		foreach (['text'=>"Joe's", 'count'=>17,'rate'=>1.5,'enabled'=>true,'disabled'=>false,'nil'=>null,'obj'=>(object)['a'=>2],'list'=>['b'=>3]] as $name=>$value) {
			$this->assertTrue($s->persistValue('g',$name,$value));
		}
		$this->assertCount(9,$db->nonQueries);
		$this->assertStringContainsString("Joe\\'s",$db->nonQueries[1]);
		$this->assertStringContainsString("'int'",$db->nonQueries[2]);
		$this->assertStringContainsString("'float'",$db->nonQueries[3]);
		$this->assertStringContainsString("'bool'",$db->nonQueries[4]);
		$this->assertStringContainsString("'null'",$db->nonQueries[6]);
		$this->assertStringContainsString("'array'",$db->nonQueries[7]);
		$this->assertSame(['b'=>3],$s->getValue('g','list'));
		$this->assertFalse($s->isDirty());
	}

	public function testSaveSynchronizesObsoleteRecordsWithoutDroppingValidKeys(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$db->multiRows=[['group'=>'g','name'=>'obsolete','type'=>'int','value'=>'1']];
		$s=new \Base3\Configuration\Database\DatabaseConfiguration($db);
		$this->assertSame(1,$s->getValue('g','obsolete'));
		$s->removeValue('g','obsolete');
		$s->setValue('g','replacement',2);
		$this->assertTrue($s->saveIfDirty());
		$this->assertFalse($s->isDirty());
		$this->assertCount(3,$db->nonQueries);
		$this->assertStringContainsString('DELETE FROM',$db->nonQueries[2]);
	}

	public function testInvalidDatabaseRowAndInvalidEncodedArrayFailClosed(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();
		$db->multiRows=[['group'=>'base','name'=>'list','type'=>'array','value'=>'{"broken"]']];
		$s=new \Base3\Configuration\Database\DatabaseConfiguration($db);
		try { $s->get();$this->fail('Expected invalid JSON failure'); }
		catch (\RuntimeException $e) { $this->assertStringContainsString('Failed to decode configuration value',$e->getMessage()); }
		$db->multiRows=[['group'=>'base','name'=>'list','type'=>'array','value'=>'"scalar"']];
		$this->expectException(\RuntimeException::class);
		$s->reload();
	}

	public function testConnectionIsRequiredAndDatabaseErrorsAreNotIgnored(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$db->connectedFlag=false;
		$s=new \Base3\Configuration\Database\DatabaseConfiguration($db);
		try { $s->get();$this->fail('Expected missing database connection'); }
		catch (\RuntimeException $e) { $this->assertStringContainsString('Failed to connect to database',$e->getMessage()); }
		$db->connectedFlag=true;$db->error=true;$db->errorCode=7;$db->errorText='permission denied';
		$this->expectException(\RuntimeException::class);
		$s->reload();
	}

	public function testInvalidKeysAndUnserializableValuesAreRejected(): void {
		$db=new \Base3\Test\Support\RecordingDatabaseStub();$s=new \Base3\Configuration\Database\DatabaseConfiguration($db);
		try { $s->persistValue('', 'key', 1);$this->fail('Expected invalid group'); }
		catch (\RuntimeException $e) { $this->assertStringContainsString('group must not be empty',$e->getMessage()); }
		$this->expectException(\RuntimeException::class);
		$s->persistValue('g','n',['bad'=>INF]);
	}

}

require_once __DIR__ . '/../../Support/RecordingDatabaseStub.php';
