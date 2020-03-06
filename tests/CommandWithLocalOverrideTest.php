<?php

namespace Signifly\EventSourceGenerator\Tests;

use Wnx\LaravelStats\ReflectionClass;

class CommandWithLocalOverrideTest extends SimpleTestCase
{
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new ReflectionClass('SimpleTest\CommandWithLocalOverride');
    }

    /** @test */
    public function does_not_have_a_description()
    {
        $this->assertFalse($this->command->getDocComment());
    }

    /**
     * Non-nullable nullable UUID field.
     */

    /** @test */
    public function has_a_nonnullable_nullable_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('localOverrideNullableUuid');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_nonnullable_nullable_from_field_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('localOverrideNullableUuid');

        $this->assertFalse($method->isPublic());
        $this->assertFalse($method->isStatic());
        $this->assertTrue($method->getType()->allowsNull());
    }

    /**
     * Overridden description UUID field.
     */

    /** @test */
    public function has_a_description_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('localOverrideUuidDescription');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_description_from_field_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('localOverrideUuidDescription');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $this->assertEquals('World, Hello', $docblock->getSummary());
    }

    /**
     * Example UUID field.
     */

    /** @test */
    public function has_a_example_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('localOverrideUuidExample');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_example_from_field_definition()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('localOverrideUuidExample');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $tags = $docblock->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals('example', $tags[0]->getName());
        $this->assertEquals('@example 4321', $tags[0]->render());
    }

    /**
     * Composed UUID field.
     */

    /** @test */
    public function has_a_composed_uuid_field_attribute()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('composedOverride');
        $this->assertNotNull($method);
    }

    /** @test */
    public function has_inherited_from_field_definition_with_fallback_to_type_definition_and_local_overrides()
    {
        /** @var \ReflectionProperty $method */
        $method = $this->command->getProperty('composedOverride');
        $docblock = $this->factoryDocBlock->create($method->getDocComment());

        $this->assertTrue($method->getType()->allowsNull());
        $this->assertEquals('Wow', $docblock->getSummary());
        $tags = $docblock->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals('example', $tags[0]->getName());
        $this->assertEquals('@example 1234', $tags[0]->render());
    }
}
