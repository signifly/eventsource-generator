<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor\Lexers;

use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Lexers\InterfaceLexer;

class InterfaceLexerTest extends TestCase
{
    protected Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new InterfaceLexer();
    }

    /** @test */
    public function it_keeps_alias_as_is_if_no_namespace_is_defined()
    {
        $tokens = [
            'interfaces' => [
                'jsonPayload' => '\JsonSerializeable',
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertEquals($tokens, $result);
    }

    /** @test */
    public function it_considers_empty_concrete_to_be_the_same_as_alias()
    {
        $tokens = [
            'interfaces' => [
                'JsonSerializeable' => null,
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertEquals([
            'interfaces' => [
                'JsonSerializeable' => 'JsonSerializeable',
            ],
        ], $result);
    }

    /** @test */
    public function it_prefixes_alias_if_absolute_namespace_is_defined()
    {
        $tokens = [
            'namespace' => '\\SomeNamespace',
            'interfaces' => [
                'jsonPayload' => '\JsonSerializeable',
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertEquals([
            'interfaces' => [
                '\\SomeNamespace\\jsonPayload' => '\JsonSerializeable',
            ],
        ], $result);
    }

    // todo: should probably not prefix with absolute namespace, but the namespace
    //       the parsed file is located in?

    /** @test */
    public function it_prefixes_alias_with_absolute_namespace_if_relative_namespace_is_defined()
    {
        $tokens = [
            'namespace' => 'SomeNamespace',
            'interfaces' => [
                'jsonPayload' => '\JsonSerializeable',
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertEquals([
            'interfaces' => [
                '\\SomeNamespace\\jsonPayload' => '\JsonSerializeable',
            ],
        ], $result);
    }

    /** @test */
    public function it_prefixes_concrete_if_absolute_namespace_is_defined_and_concrete_is_relative()
    {
        $tokens = [
            'namespace' => '\\SomeNamespace',
            'interfaces' => [
                'jsonPayload' => 'JsonSerializeable',
            ],
        ];

        $result = $this->lexer->analyze($tokens);

        $this->assertEquals([
            'interfaces' => [
                '\\SomeNamespace\\jsonPayload' => '\\SomeNamespace\\JsonSerializeable',
            ],
        ], $result);
    }
}
