<?php

namespace Signifly\EventSourceGenerator\Tests;

use Wnx\LaravelStats\ReflectionClass;

class CommandWithSingleInheritanceTest extends SimpleTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ReflectionClass('SimpleTest\CommandWithSingleInheritance');
    }

    /** @test */
    public function does_not_have_a_description()
    {
        $this->assertFalse($this->command->getDocComment());
    }

    /** @test */
    public function has_a_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('uuidField');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_type_from_type_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('uuidField');

        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertEquals('Ramsey\Uuid\UuidInterface', $method->getType()->getName());
    }

    /** @test */
    public function has_inherited_nullable_from_type_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('uuidField');

        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertTrue($method->getType()->allowsNull());
    }

    /** @test */
    public function has_inherited_description_from_type_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('uuidField');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $this->assertEquals('UUID v4', $docblock->getSummary());
    }

    /** @test */
    public function has_inherited_example_from_type_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('uuidField');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $tags = $docblock->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals('example', $tags[0]->getName());
        $this->assertEquals('@example "c0b47bc5-2aaa-497b-83cb-11d97da03a95"', $tags[0]->render());
    }

    /** @test */
    public function has_a_uuid_field_accessor()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'uuidField');
        $this->assertNotNull($method);
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertEquals('Ramsey\Uuid\UuidInterface', $method->getReturnType()->getName());
    }

    /** @test */
    public function optionally_allows_a_single_uuid_in_constructor()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getConstructor();

        $this->assertNotNull($method);
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
        // todo: make it default null?
        $this->assertEquals(1, $method->getNumberOfRequiredParameters());
        $this->assertEquals('Ramsey\Uuid\UuidInterface', $method->getParameters()[0]->getType()->getName());
    }

    /** @test */
    public function has_a_with_test_helper_method_for_string_field()
    {
        /** @var \ReflectionMethod $method */
        $method = $this->command->getDefinedMethods()->firstWhere('name', 'withUuidField');

        $this->assertNotNull($method);
        $this->assertEquals('self', $method->getReturnType()->getName());
        $this->assertEquals(1, $method->getNumberOfParameters());
        $this->assertEquals(1, $method->getNumberOfRequiredParameters());
        $this->assertEquals('Ramsey\Uuid\UuidInterface', $method->getParameters()[0]->getType()->getName());
    }
}
