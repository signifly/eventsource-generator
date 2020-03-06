<?php

namespace Signifly\EventSourceGenerator\Tests;

use Wnx\LaravelStats\ReflectionClass;

class CommandWithDescriptionTest extends SimpleTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ReflectionClass('SimpleTest\CommandWithDescription');
    }

    /** @test */
    public function has_a_description_docblock()
    {
        $docblock = $this->factoryDocBlock->create($this->command->getDocComment());

        $this->assertEquals('Im a command', $docblock->getSummary());
    }

    /** @test */
    public function has_a_topayload_method()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'toPayload');
        $this->assertNotNull($method);
        $this->assertFalse($method->isStatic());
        $this->assertEquals('array', $method->getReturnType()->getName());
    }

    /** @test */
    public function has_a_frompayload_method()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'fromPayload');
        $this->assertNotNull($method);
        $this->assertTrue($method->isStatic());
        $this->assertEquals('self', $method->getReturnType()->getName());
    }

    /** @test */
    public function has_a_with_test_helper_method()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'with');
        $this->assertNotNull($method);
        $this->assertTrue($method->isStatic());
        $this->assertEquals('self', $method->getReturnType()->getName());
    }

    /** @test */
    public function has_an_empty_constructor()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getConstructor();
        $this->assertNotNull($method);
        $this->assertTrue($method->isPublic());
        $this->assertEquals(0, $method->getNumberOfParameters());
    }
}
