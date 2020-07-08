<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Lexers\CommandLexer;
use Signifly\EventSourceGenerator\Lexers\ComputedLexer;
use Signifly\EventSourceGenerator\Lexers\FieldLexer;
use Signifly\EventSourceGenerator\Models\Field;
use Signifly\EventSourceGenerator\Tests\Fixtures\NoopInterface;

class CommandLexerTest extends TestCase
{
    protected Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new CommandLexer(new FieldLexer(), new ComputedLexer());
    }

    /** @test */
    public function it_parses_name()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Command $command */
        $command = array_pop($result['commands']);

        $this->assertEquals('someCommand', $command->getName());
    }

    /** @test */
    public function it_parses_empty_namespace()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('someCommand', $result['commands']);

        /** @var Command $command */
        $command = array_pop($result['commands']);

        $this->assertEquals('', $command->getNamespace());
    }

    /** @test */
    public function it_parses_relative_namespace()
    {
        $tokens = [
            'namespace' => 'MyNamespace',
            'commands' => [
                'someCommand' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('MyNamespace\\someCommand', $result['commands']);

        /** @var Command $command */
        $command = array_pop($result['commands']);

        $this->assertEquals('MyNamespace', $command->getNamespace());
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
            'commands' => [
                'someCommand' => [
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('\\MyNamespace\\someCommand', $result['commands']);

        /** @var Command $command */
        $command = array_pop($result['commands']);

        $this->assertEquals('\\MyNamespace', $command->getNamespace());
    }

    /** @test */
    public function it_parses_description()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'description' => 'some event description',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

        $this->assertEquals('some event description', $event->getDescription());
    }

    /** @test */
    public function it_parses_a_single_interface()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'implements' => [
                        'aliasedInterface',
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

        $this->assertEquals(['aliasedInterface'], $event->getInterfaces());
    }

    /** @test */
    public function it_parses_a_mutiple_interface()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'implements' => [
                        'aliasedInterface',
                        NoopInterface::class,
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

        $this->assertEquals([
            'aliasedInterface',
            NoopInterface::class,
        ], $event->getInterfaces());
    }

    /** @test */
    public function it_parses_a_simple_fields_from()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'fieldsFrom' => [
                        ['name' => 'parentField'],
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

        $this->assertEquals([
            ['name' => 'parentField'],
        ], $event->getFieldsFrom());
    }

    /** @test */
    public function it_parses_fields_from_with_exception()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'fieldsFrom' => [
                        ['name' => 'parentField', 'except' => ['fieldOne', 'fieldTwo']],
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

        $this->assertEquals([
            ['name' => 'parentField', 'except' => ['fieldOne', 'fieldTwo']],
        ], $event->getFieldsFrom());
    }

    /** @test */
    public function it_parses_an_aliased_field()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'fields' => [
                        'someField' => null,
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

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
            'commands' => [
                'someCommand' => [
                    'fields' => [
                        'someField' => null,
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Event $event */
        $event = array_pop($result['commands']);

        $this->assertCount(1, $event->getFields());
        /** @var Field $field */
        $field = Arr::first($event->getFields());
        $this->assertEquals('someField', $field->getName());
        $this->assertEquals('\MyNamespace\someField', $field->getType());
        $this->assertEquals('MyNamespace', $field->getNamespace());
    }

    /** @test */
    public function it_parses_single_computed_field()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'computed' => [
                        'myMethod' => [
                            'type' => 'string',
                            'value' => "return 'string';",
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Command $command */
        $command = array_pop($result['commands']);

        $this->assertCount(1, $command->getComputed());
        $this->assertEquals(['myMethod'], array_keys($command->getComputed()));
        /** @var Computed $computed */
        $computed = Arr::first($command->getComputed());
        $this->assertEquals('string', $computed->getType());
    }

    /** @test */
    public function it_parses_multiple_computed_field()
    {
        $tokens = [
            'commands' => [
                'someCommand' => [
                    'computed' => [
                        'myMethod' => [
                            'type' => 'string',
                            'value' => "return 'string';",
                        ],
                        'anotherMethod' => [
                            'type' => 'int',
                            'value' => 'return 5;',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Command $command */
        $command = array_pop($result['commands']);

        $this->assertCount(2, $command->getComputed());
        $this->assertEquals(['myMethod', 'anotherMethod'], array_keys($command->getComputed()));
        /** @var Computed $computed */
        $computed = Arr::first($command->getComputed());
        $this->assertEquals('string', $computed->getType());

        /** @var Computed $computed */
        $computed = Arr::last($command->getComputed());
        $this->assertEquals('int', $computed->getType());
    }
}
