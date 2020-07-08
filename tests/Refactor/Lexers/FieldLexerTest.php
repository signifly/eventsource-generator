<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor\Lexers;

use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Lexers\FieldLexer;

class FieldLexerTest extends TestCase
{
    protected Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new FieldLexer();
    }

    /** @test */
    public function it_parses_name()
    {
        $tokens = [
            'fields' => [
                'someField' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Field $field */
        $field = array_pop($result['fields']);

        $this->assertEquals('someField', $field->getName());
    }

    /** @test */
    public function it_parses_field_token()
    {
        $tokens = [
            'fields' => [
                'someField' => [
                    'field' => 'myField',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Field $field */
        $field = array_pop($result['fields']);

        $this->assertEquals('myField', $field->getTemplate());
    }

    /** @test */
    public function it_parses_empty_namespace()
    {
        $tokens = [
            'fields' => [
                'someField' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Field $field */
        $field = array_pop($result['fields']);

        $this->assertEquals('', $field->getNamespace());
    }

    /** @test */
    public function it_parses_relative_namespace()
    {
        $tokens = [
            'namespace' => 'MyNamespace',
            'fields' => [
                'someField' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Field $field */
        $field = array_pop($result['fields']);

        $this->assertEquals('MyNamespace', $field->getNamespace());
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
            'fields' => [
                'someField' => [
                    'type' => 'myType',
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Field $field */
        $field = array_pop($result['fields']);

        $this->assertEquals('\\MyNamespace', $field->getNamespace());
    }

    /**
     * @test
     * @dataProvider fieldDataProvider
     * @covers \Signifly\EventSourceGenerator\Models\Field::getType()
     * @covers \Signifly\EventSourceGenerator\Models\Field::getNullable()
     * @covers \Signifly\EventSourceGenerator\Models\Field::getDescription()
     * @covers \Signifly\EventSourceGenerator\Models\Field::getExample()
     * @covers \Signifly\EventSourceGenerator\Models\Field::getSerializer()
     * @covers \Signifly\EventSourceGenerator\Models\Field::getUnserializer()
     */
    public function it_parses_fields($fieldName, $value): void
    {
        $tokens = [
            'fields' => [
                'someField' => [
                    $fieldName => $value,
                ],
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $field = $result['fields']['someField'];

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
            'fields' => [
                'someField' => null,
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        /** @var Field $field */
        $field = array_pop($result['fields']);
        $this->assertEquals('someField', $field->getName());
        $this->assertEquals('someField', $field->getType());
    }
}
