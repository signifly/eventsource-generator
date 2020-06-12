<?php

namespace Signifly\EventSourceGenerator\Lexers;

use Illuminate\Support\Str;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Models\Type;

class TypeLexer implements Lexer
{
    public function analyze(array $tokens): array
    {
        $registry = ['types' => []];

        if (empty($tokens['types'])) {
            return $registry;
        }

        foreach ($tokens['types'] as $name => $definition) {
            $type = $this->analyzeToken(
                $name,
                $definition,
                $tokens['namespace'] ?? null
            );

            $registry['types'][$type->getFqcn()] = $type;
        }

        return $registry;
    }

    protected function analyzeToken(string $name, ?array $definition, string $namespace = null)
    {
        $fqcn = $namespace ?? false;
        if ($fqcn && ! Str::contains($name, '\\')) {
            $name = '\\'.ltrim($fqcn, '\\').'\\'.$name;
        }

        $field = new Type($name);

        if ($definition === null) {
            return $field->setType($name);
        }

        if (isset($definition['type'])) {
            $field->setType($definition['type']);
        }
        if (isset($definition['nullable'])) {
            $field->setNullable($definition['nullable']);
        }
        if (isset($definition['description'])) {
            $field->setDescription($definition['description']);
        }
        if (isset($definition['example'])) {
            $field->setExample($definition['example']);
        }
        if (isset($definition['serializer'])) {
            $field->setSerializer($definition['serializer']);
        }
        if (isset($definition['unserializer'])) {
            $field->setUnserializer($definition['unserializer']);
        }

        return $field;
    }
}
