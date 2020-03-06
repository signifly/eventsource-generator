<?php

namespace Signifly\EventSourceGenerator\Tests;

use Wnx\LaravelStats\ReflectionClass;

class CommandWithSimpleFieldTest extends SimpleTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ReflectionClass('SimpleTest\CommandWithSimpleField');
    }

    /** @test */
    public function does_not_have_a_description()
    {
        $this->assertFalse($this->command->getDocComment());
    }

    /** @test */
    public function has_a_simple_string_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('simpleString');
        $this->assertNotNull($method);
        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertEquals('string', $method->getType()->getName());
    }

    /** @test */
    public function has_a_simple_string_field_accessor()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'simpleString');
        $this->assertNotNull($method);
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    /** @test */
    public function requires_a_single_string_in_constructor()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getConstructor();
        $this->assertNotNull($method);
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals(1, $method->getNumberOfRequiredParameters());
        $this->assertEquals('string', $method->getParameters()[0]->getType()->getName());
    }

    /** @test */
    public function has_a_with_test_helper_method_for_string_field()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'withSimpleString');
        $this->assertNotNull($method);
        $this->assertEquals('self', $method->getReturnType()->getName());
        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals(1, $method->getNumberOfRequiredParameters());
        $this->assertEquals('string', $method->getParameters()[0]->getType()->getName());
    }
}
