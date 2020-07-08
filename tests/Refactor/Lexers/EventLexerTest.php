<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor\Lexers;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Lexers\EventLexer;
use Signifly\EventSourceGenerator\Lexers\FieldLexer;
use Signifly\EventSourceGenerator\Models\Field;
use Signifly\EventSourceGenerator\Tests\Fixtures\NoopInterface;

class EventLexerTest extends TestCase
{
    protected Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new EventLexer(new FieldLexer());
    }

    /** @test */
    public function it_parses_name()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Type $type */
        $type = array_pop($result['events']);

        $this->assertEquals('someEvent', $type->getName());
    }

    /** @test */
    public function it_parses_empty_namespace()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('someEvent', $result['events']);

        /** @var Type $type */
        $type = array_pop($result['events']);

        $this->assertEquals('', $type->getNamespace());
    }

    /** @test */
    public function it_parses_relative_namespace()
    {
        $tokens = [
            'namespace' => 'MyNamespace',
            'events' => [
                'someEvent' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('MyNamespace\\someEvent', $result['events']);

        /** @var Type $type */
        $type = array_pop($result['events']);

        $this->assertEquals('MyNamespace', $type->getNamespace());
    }

    /** @test */
    public function it_parses_absolute_namespace()
    {
        $this->markTestSkipped('
            Right now, base Model strips the leading slash, making the namespace relative.
            Handling of namespace in base model might need revisiting.
        ');

        $tokens = [
            'namespace' => '\\MyNamespace',
            'events' => [
                'someEvent' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('\\MyNamespace\\someEvent', $result['events']);

        /** @var Type $type */
        $type = array_pop($result['events']);

        $this->assertEquals('\\MyNamespace', $type->getNamespace());
    }

    /** @test */
    public function it_parses_description()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                    'description' => 'some event description',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertEquals('some event description', $event->getDescription());
    }

    /** @test */
    public function it_parses_a_single_interface()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                    'implements' => [
                        'aliasedInterface',
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertEquals(['aliasedInterface'], $event->getInterfaces());
    }

    /** @test */
    public function it_parses_a_mutiple_interface()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                    'implements' => [
                        'aliasedInterface',
                        NoopInterface::class,
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertEquals([
            'aliasedInterface',
            NoopInterface::class,
        ], $event->getInterfaces());
    }

    /** @test */
    public function it_parses_a_simple_fields_from()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                    'fieldsFrom' => [
                        ['name' => 'parentField'],
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertEquals([
            ['name' => 'parentField'],
        ], $event->getFieldsFrom());
    }

    /** @test */
    public function it_parses_fields_from_with_exception()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                    'fieldsFrom' => [
                        ['name' => 'parentField', 'except' => ['fieldOne', 'fieldTwo']],
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertEquals([
            ['name' => 'parentField', 'except' => ['fieldOne', 'fieldTwo']],
        ], $event->getFieldsFrom());
    }

    /** @test */
    public function it_parses_an_aliased_field()
    {
        $tokens = [
            'events' => [
                'someEvent' => [
                    'fields' => [
                        'someField' => null,
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertCount(1, $event->getFields());
        /** @var Field $field */
        $field = Arr::first($event->getFields());
        $this->assertEquals('someField', $field->getName());
        $this->assertEquals('someField', $field->getType());
        $this->assertEquals('', $field->getNamespace());
    }

    /** @test */
    public function it_parses_an_aliased_field_with_namespace()
    {
        $tokens = [
            'namespace' => 'MyNamespace',
            'events' => [
                'someEvent' => [
                    'fields' => [
                        'someField' => null,
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['events']);

        $this->assertCount(1, $event->getFields());
        /** @var Field $field */
        $field = Arr::first($event->getFields());
        $this->assertEquals('someField', $field->getName());
        $this->assertEquals('\MyNamespace\someField', $field->getType());
        $this->assertEquals('MyNamespace', $field->getNamespace());
    }
}
