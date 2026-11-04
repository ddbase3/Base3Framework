<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy;

use PHPUnit\Framework\TestCase;

final class AbstractJobExecutionPolicyBehaviorTest extends TestCase {

	public function testScalarValueConversionsAndDefaults(): void {
		$policy = new InspectableJobPolicyFixture();
		$policy->setData(['text'=>17, 'number'=>'14', 'list'=>['not scalar'], 'negative'=>-3]);
		$this->assertSame('17',$policy->readString('text'));
		$this->assertSame(14,$policy->readInt('number'));
		$this->assertSame(-3,$policy->readInt('negative'));
		$this->assertSame('fallback',$policy->readString('list','fallback'));
		$this->assertSame(7,$policy->readInt('list',7));
		$this->assertSame(8,$policy->readInt('missing',8));
	}

	public function testPolicyIdSupportsAliasAndRejectsNonScalarIds(): void {
		$policy = new InspectableJobPolicyFixture();
		$policy->setData(['policy_id'=>'  original  ']);
		$this->assertSame('original',$policy->readPolicyId());
		$policy->setData(['id'=>' newest ','policy_id'=>'original']);
		$this->assertSame('newest',$policy->readPolicyId());
		$policy->setData(['id'=>['not scalar']]);
		$this->assertNull($policy->readPolicyId());
		$policy->setData(['id'=>'   ']);
		$this->assertNull($policy->readPolicyId());
	}

	public function testStateKeysNormalizeAllSegmentsWithoutLeakingSpaces(): void {
		$policy = new InspectableJobPolicyFixture();
		$this->assertSame('worker.job.my_job.cron.last_run',$policy->readStateKey('My Job','Cron','Last Run'));
		$this->assertSame('worker.job.my_job.interval.policy_7.last_run',$policy->readStateKey('My Job','Interval','Last Run','Policy-7'));
		$this->assertSame('worker.job.default.default.default',$policy->readStateKey('!!!','___','-'));
	}

	public function testDailyAndOvernightWindowsHaveExclusiveEnd(): void {
		$policy = new InspectableJobPolicyFixture();
		$this->assertTrue($policy->readTimeInWindow('12:00','09:00','17:00'));
		$this->assertFalse($policy->readTimeInWindow('17:00','09:00','17:00'));
		$this->assertTrue($policy->readTimeInWindow('23:59','22:00','04:00'));
		$this->assertTrue($policy->readTimeInWindow('01:00','22:00','04:00'));
		$this->assertFalse($policy->readTimeInWindow('04:00','22:00','04:00'));
		$this->assertFalse($policy->readTimeInWindow('14:00','22:00','04:00'));
	}

	public function testDateParsingReasonAndDefaultSchema(): void {
		$policy = new InspectableJobPolicyFixture();
		$this->assertTrue($policy->readSameDay(date('Y-m-d H:i:s')));
		$this->assertFalse($policy->readSameDay('not a date'));
		$policy->changeReason('blocked');
		$this->assertSame('blocked',$policy->getReason());
		$this->assertSame([],$policy->getSchema());
		$this->assertSame('inspectablejobpolicyfixture',$policy::getName());
		$policy->markRun('job');
		$this->assertSame('blocked',$policy->getReason());
	}
}

require_once __DIR__ . '/InspectableJobPolicyFixture.php';
