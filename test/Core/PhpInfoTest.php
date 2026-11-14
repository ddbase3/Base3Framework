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

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use Base3\Core\PhpInfo;

class PhpInfoTest extends TestCase
{
    private PhpInfo $phpInfo;

    protected function setUp(): void
    {
        putenv('DEBUG=1'); // für Test aktivieren
        $this->phpInfo = new PhpInfo();
    }

    public function testGetName(): void
    {
        $this->assertSame('phpinfo', $this->phpInfo->getName());
    }

    public function testGetOutputWithDebugEnabled(): void
    {
        ob_start();
        $this->phpInfo->getOutput();
        $output = ob_get_clean();

        // Inhalt muss phpinfo enthalten
        $this->assertStringContainsString('phpinfo', strtolower($output));
        $this->assertStringContainsString('PHP Version', $output);
    }

    public function testGetOutputWithDebugDisabled(): void
    {
        putenv('DEBUG='); // deaktivieren
        $output = $this->phpInfo->getOutput();
        $this->assertSame('', $output);
    }

    public function testGetHelp(): void
    {
        $this->assertStringContainsString('phpinfo', $this->phpInfo->getHelp());
    }
}

