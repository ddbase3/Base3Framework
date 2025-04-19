<?php

namespace Base3\Test\Core;

use PHPUnit\Framework\TestCase;
use Base3\Core\Autoloader;

class AutoloaderTest extends TestCase
{
    public function testAutoloaderLoadsDummyClass(): void
    {
        $dummy = new \Base3\Test\Dummy\DummyClass();
        $this->assertSame('Hello from DummyClass!', $dummy->sayHello());
    }
}

