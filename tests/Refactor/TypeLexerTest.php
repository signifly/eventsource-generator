<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor;

use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Lexers\TypeLexer;

class TypeLexerTest extends TestCase
{
    protected Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new TypeLexer();
    }

    /** @test */
    public function it_parses_name()
    {
        $tokens = [
            'types' => [
                'someType' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Type $type */
        $type = array_pop($result['types']);

        $this->assertEquals('someType', $type->getName());
    }

    /** @test */
    public function it_parses_empty_namespace()
    {
        $tokens = [
            'types' => [
                'someType' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('someType', $result['types']);

        /** @var Type $type */
        $type = array_pop($result['types']);

        $this->assertEquals('', $type->getNamespace());
    }

    /** @test */
    public function it_parses_relative_namespace()
    {
        $tokens = [
            'namespace' => 'MyNamespace',
            'types' => [
                'someType' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('MyNamespace\\someType', $result['types']);

        /** @var Type $type */
        $type = array_pop($result['types']);

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
            'types' => [
                'someType' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertArrayHasKey('\\MyNamespace\\someType', $result['types']);

        /** @var Type $type */
        $type = array_pop($result['types']);

        $this->assertEquals('\\MyNamespace', $type->getNamespace());
    }

    /**
     * @test
     * @dataProvider fieldDataProvider
     * @covers \Signifly\EventSourceGenerator\Models\Type::getType()
     * @covers \Signifly\EventSourceGenerator\Models\Type::getNullable()
     * @covers \Signifly\EventSourceGenerator\Models\Type::getDescription()
     * @covers \Signifly\EventSourceGenerator\Models\Type::getExample()
     * @covers \Signifly\EventSourceGenerator\Models\Type::getSerializer()
     * @covers \Signifly\EventSourceGenerator\Models\Type::getUnserializer()
     */
    public function it_parses_fields($fieldName, $value): void
    {
        $tokens = [
            'types' => [
                'someType' => [
                    $fieldName => $value,
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $field = $result['types']['someType'];

        $method = 'get'.ucfirst($fieldName);
        $this->assertEquals($value, $field->{$method}());
    }

    public function fieldDataProvider()
    {
        return [
            'type' => ['type', 'myType'],
            'nullable, true' => ['nullable', true],
            'nullable, false' => ['nullable', false],
            'description' => ['description', 'awesome description'],
            'example' => ['example', 'simple example'],
            'serializer' => ['serializer', 'someSerializer'],
            'unserializer' => ['unserializer', 'anotherUnserializer'],
        ];
    }

    /** @test */
    public function it_considers_empty_definition_to_be_an_alias_of_a_type()
    {
        $tokens = [
            'types' => [
                'someType' => null,
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Type $type */
        $type = array_pop($result['types']);
        $this->assertEquals('someType', $type->getName());
        $this->assertEquals('someType', $type->getType());
    }
}
