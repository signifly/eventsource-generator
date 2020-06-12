<?php

namespace Signifly\EventSourceGenerator\Lexers;

use Signifly\EventSourceGenerator\Models\Field;

class FieldLexer extends TypeLexer
{
    public function analyze(array $tokens): array
    {
        $registry = ['fields' => []];

        if (empty($tokens['fields'])) {
            return $registry;
        }

        foreach ($tokens['fields'] as $name => $definition) {
            $type = $this->analyzeToken(
                $name,
                $definition,
                $tokens['namespace'] ?? null
            );

            $field = Field::fromType($type);

            if (isset($definition['field'])) {
                $field->setTemplate($field);
            }

            $registry['fields'][$field->getFqcn()] = $field;
        }

        return $registry;
    }
}
