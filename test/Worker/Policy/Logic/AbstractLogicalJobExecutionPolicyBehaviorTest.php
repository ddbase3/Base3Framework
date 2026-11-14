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

namespace Base3\Test\Worker\Policy\Logic;

use Base3\Test\Support\StaticClassMapStub;
use Base3\Core\Worker\Policy\Timing\AlwaysRunJobPolicy;
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
