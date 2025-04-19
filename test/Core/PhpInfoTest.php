<?php declare(strict_types=1);

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

