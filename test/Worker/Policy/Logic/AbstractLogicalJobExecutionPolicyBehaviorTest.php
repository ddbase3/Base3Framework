<?php declare(strict_types=1);

namespace Base3\Test\Worker\Policy\Logic;

use Base3\Test\Support\StaticClassMapStub;
use Base3\Worker\Policy\Timing\AlwaysRunJobPolicy;
use PHPUnit\Framework\TestCase;

final class AbstractLogicalJobExecutionPolicyBehaviorTest extends TestCase {

	private function createPolicy(): InspectableLogicalJobPolicyFixture {
		$classmap = new StaticClassMapStub();
		$classmap->register('alwaysrunjobpolicy',new AlwaysRunJobPolicy());
		return new InspectableLogicalJobPolicyFixture($classmap);
	}

	public function testListAndItemsDefinitionsAreAccepted(): void {
		$policy = $this->createPolicy();
		$this->assertTrue($policy->exposeIsList([['policy'=>'x'],['policy'=>'y']]));
		$this->assertFalse($policy->exposeIsList(['name'=>['policy'=>'x']]));
		$policy->setData([['policy'=>'alwaysrunjobpolicy'], ['policy'=>'unknown']]);
		$this->assertSame(1, $policy->countPolicies());
		$this->assertTrue($policy->shouldRun('job'));
		$policy->setData(['items'=>[['policy'=>'alwaysrunjobpolicy']]]);
		$this->assertSame(1, $policy->countPolicies());
	}

	public function testInvalidDefinitionsDoNotInstallPolicies(): void {
		$policy = $this->createPolicy();
		$policy->setData(['items'=>[['data'=>[]], ['policy'=>[]], ['policy'=>'missing']]]);
		$this->assertSame(0, $policy->countPolicies());
		$this->assertFalse($policy->shouldRun('job'));
		$policy->setData(['foo'=>'not a list']);
		$this->assertSame(0, $policy->countPolicies());
	}

	public function testResetAndNormalizationUseOneSetOfDefinitions(): void {
		$policy = $this->createPolicy();
		$policy->setData(['items'=>[['policy'=>'alwaysrunjobpolicy','data'=>'not array']]]);
		$this->assertSame(1, $policy->countPolicies());
		$policy->setData([]);
		$this->assertSame(0, $policy->countPolicies());
		$this->assertSame([], $policy->exposeDefinitions(['other'=>'ignored']));
		$this->assertSame([['policy'=>'yes']], $policy->exposeDefinitions(['items'=>[['policy'=>'yes']]]));
	}
}

require_once __DIR__ . '/InspectableLogicalJobPolicyFixture.php';
require_once __DIR__ . '/../../../Support/StaticClassMapStub.php';
