<?php

namespace Signifly\EventSourceGenerator\Tests\Refactor;

use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\AST;
use Signifly\EventSourceGenerator\Lexers\CommandLexer;
use Signifly\EventSourceGenerator\Lexers\ComputedLexer;
use Signifly\EventSourceGenerator\Lexers\EventLexer;
use Signifly\EventSourceGenerator\Lexers\FieldLexer;
use Symfony\Component\Yaml\Yaml;

class ASTTest extends TestCase
{
    protected AST $ast;
    protected CommandLexer $lexer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ast = new AST();
        $this->lexer = new CommandLexer(
            new FieldLexer(),
            new ComputedLexer()
        );

        $yaml = Yaml::parse('commands:
   Parent:
    fields:
      Field1:
      Field2:
   Child:
    fieldsFrom:
    - name: Parent
   SpecialChild:
    fieldsFrom:
    - name: Parent
      except:
      - Field2
');

        $analysis = $this->lexer->analyze($yaml);
        $this->ast->addAnalysis($analysis);
    }

    /** @test */
    public function it_resolves_simple_field_list()
    {
        $parent = $this->ast->commands('Parent');

        $fields = $this->ast->resolveFieldsFor($parent);

        $this->assertCount(2, $fields);
        $this->assertEquals(['Field1', 'Field2'], array_keys($fields));
    }

    /** @test */
    public function it_resolves_a_single_fields_from()
    {
        $child = $this->ast->commands('Child');

        $fields = $this->ast->resolveFieldsFor($child);

        $this->assertCount(2, $fields);
        $this->assertEquals(['Field1', 'Field2'], array_keys($fields));
    }

    /** @test */
    public function it_resolves_a_single_fields_from_with_an_exception()
    {
        $special = $this->ast->commands('SpecialChild');

        $fields = $this->ast->resolveFieldsFor($special);

        $this->assertCount(1, $fields);
        $this->assertEquals(['Field1'], array_keys($fields));
    }

    /** @test */
    public function it_resolves_double_inheritance()
    {
        $this->ast->addAnalysis(
            $this->lexer->analyze(Yaml::parse('commands:
               ChildsChild:
                fieldsFrom:
                - name: Child
            '))
        );
        $child = $this->ast->commands('ChildsChild');

        $fields = $this->ast->resolveFieldsFor($child);

        $this->assertCount(2, $fields);
        $this->assertEquals(['Field1', 'Field2'], array_keys($fields));
    }

    /** @test */
    public function it_resolves_double_inheritance_with_an_exception()
    {
        $this->ast->addAnalysis(
            $this->lexer->analyze(Yaml::parse('commands:
               ChildsChild:
                fieldsFrom:
                - name: Child
                  except:
                  - Field1
            '))
        );
        $child = $this->ast->commands('ChildsChild');

        $fields = $this->ast->resolveFieldsFor($child);

        $this->assertCount(1, $fields);
        $this->assertEquals(['Field2'], array_keys($fields));
    }

    /** @test */
    public function it_can_combine_fields_from_with_fields_definitions()
    {
        $this->ast->addAnalysis(
            $this->lexer->analyze(Yaml::parse('commands:
               ChildsChild:
                fieldsFrom:
                - name: Parent
                fields:
                  Field3:
            '))
        );
        $child = $this->ast->commands('ChildsChild');

        $fields = $this->ast->resolveFieldsFor($child);

        $this->assertCount(3, $fields);
        $this->assertEquals(['Field1', 'Field2', 'Field3'], array_keys($fields));
    }

    /** @test */
    public function it_can_combine_fields_from_with_except_and_with_fields_definitions()
    {
        $this->ast->addAnalysis(
            $this->lexer->analyze(Yaml::parse('commands:
               ChildsChild:
                fieldsFrom:
                - name: Parent
                  except:
                  - Field2
                fields:
                  Field3:
            '))
        );
        $child = $this->ast->commands('ChildsChild');

        $fields = $this->ast->resolveFieldsFor($child);

        $this->assertCount(2, $fields);
        $this->assertEquals(['Field1', 'Field3'], array_keys($fields));
    }

    /** @test */
    public function event_can_inherit_from_command()
    {
        $lexer = new EventLexer(new FieldLexer());
        $this->ast->addAnalysis(
            $lexer->analyze(Yaml::parse('events:
               EventInheritance:
                fieldsFrom:
                - name: Parent
            '))
        );
        $event = $this->ast->events('EventInheritance');

        $fields = $this->ast->resolveFieldsFor($event);

        $this->assertCount(2, $fields);
        $this->assertEquals(['Field1', 'Field2'], array_keys($fields));
    }

    public function resolving_field_within_same_namespace_takes_precedence_over_same_field_name_in_another_namespace()
    {
        $this->markTestIncomplete();
    }

    public function resolving_field_by_short_name_is_possible_cross_namespace_if_name_is_unique()
    {
        $this->markTestIncomplete();
    }

    public function resolving_field_by_fqdn_is_possible_cross_namespace()
    {
        $this->markTestIncomplete();
    }
}
