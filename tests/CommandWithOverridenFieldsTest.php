<?php

namespace Signifly\EventSourceGenerator\Tests;

use Wnx\LaravelStats\ReflectionClass;

class CommandWithOverridenFieldsTest extends SimpleTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ReflectionClass('SimpleTest\CommandWithOverridenFields');
    }

    /** @test */
    public function does_not_have_a_description()
    {
        $this->assertFalse($this->command->getDocComment());
    }

    /**
     * Simple UUID field.
     */

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

    /**
     * Non-nullable UUID field.
     */

    /** @test */
    public function has_a_nonnullable_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('nonNullableUuid');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_nonnullable_from_field_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('nonNullableUuid');

        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertFalse($method->getType()->allowsNull());
    }

    /**
     * Overridden description UUID field.
     */

    /** @test */
    public function has_a_description_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('overrideUuidDescription');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_description_from_field_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('overrideUuidDescription');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $this->assertEquals('Hello World', $docblock->getSummary());
    }

    /**
     * Overridden example UUID field.
     */

    /** @test */
    public function has_a_example_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('overrideUuidExample');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_example_from_field_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('overrideUuidExample');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $tags = $docblock->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals('example', $tags[0]->getName());
        $this->assertEquals('@example 1234', $tags[0]->render());
    }
}
