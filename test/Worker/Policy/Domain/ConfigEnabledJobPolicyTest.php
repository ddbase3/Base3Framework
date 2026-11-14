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

namespace Base3\Test\Worker\Policy\Domain;

use PHPUnit\Framework\TestCase;

final class ConfigEnabledJobPolicyTest extends TestCase {

	public function testUsesExistingConfigurationWithoutImplicitGrant(): void {
		$config = new \Base3\Core\Configuration\ArrayConfiguration(['job' => ['cleanup.active' => 1]]);
		$p = new \Base3\Core\Worker\Policy\Domain\ConfigEnabledJobPolicy($config);
		$this->assertTrue($p->shouldRun('cleanup'));
		$this->assertFalse($p->shouldRun('backup'));
		$this->assertSame('Skip (disabled by configuration)', $p->getReason());
		$p->setData(['section' => 'worker', 'key' => 'enabled', 'expected' => 0]);
		$this->assertTrue($p->shouldRun('backup'));
		$this->assertSame('object', $p->getSchema()['type']);
	}

}
