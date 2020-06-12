<?php

namespace Signifly\EventSourceGenerator\Lexers;

use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Models\Computed;

class ComputedLexer implements Lexer
{
    public function analyze(array $definition): array
    {
        $computed = new Computed(
            $definition['type'],
            $definition['value']
        );

        if (isset($definition['description'])) {
            $computed->setDescription($definition['description']);
        }

        return [$computed];
    }
}
