<?php

namespace Signifly\EventSourceGenerator\Tests;

use Signifly\EventSourceGenerator\Tests\Fixtures\AliasedNoopInterface;
use Signifly\EventSourceGenerator\Tests\Fixtures\NoopInterface;
use Wnx\LaravelStats\ReflectionClass;

class CommandWithInterfaceTest extends SimpleTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ReflectionClass('SimpleTest\CommandWithInterfaces');
    }

    /** @test */
    public function it_implements_interface_via_fqcn()
    {
        $this->assertCount(2, $this->command->getInterfaces());
        $this->assertTrue($this->command->implementsInterface(NoopInterface::class));
    }

    /** @test */
    public function it_implements_interface_via_alias()
    {
        $this->assertCount(2, $this->command->getInterfaces());
        $this->assertTrue($this->command->implementsInterface(AliasedNoopInterface::class));
    }
}
