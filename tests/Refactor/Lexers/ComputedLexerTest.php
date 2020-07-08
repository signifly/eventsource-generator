<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor\Lexers;

use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Lexers\ComputedLexer;

class ComputedLexerTest extends TestCase
{
    protected Lexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lexer = new ComputedLexer();
    }

    /** @test */
    public function it_parses_a_basic_computed_entry()
    {
        $tokens = [
            'type' => 'string',
            'value' => "return 'string';",
        ];

        $result = $this->lexer->analyze($tokens);
        $computed = $result[0];

        $this->assertEquals(
            'string',
            $computed->getType()
        );
        $this->assertEquals(
            "return 'string';",
            $computed->getValue()
        );
        $this->assertEquals('', $computed->getDescription());
    }

    /** @test */
    public function it_parses_a_basic_computed_entry_with_description()
    {
        $tokens = [
            'type' => 'string',
            'value' => "return 'string';",
            'description' => 'returns an awesome string',
        ];

        $result = $this->lexer->analyze($tokens);
        $computed = $result[0];

        $this->assertEquals(
            'string',
            $computed->getType()
        );
        $this->assertEquals(
            "return 'string';",
            $computed->getValue()
        );
        $this->assertEquals(
            'returns an awesome string',
            $computed->getDescription()
        );
    }
}
