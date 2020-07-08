<?php

namespace Signifly\EventSourceGenerator\Lexers;

use Illuminate\Support\Str;
use Signifly\EventSourceGenerator\Contracts\Lexer;
use Signifly\EventSourceGenerator\Models\Event;

class EventLexer implements Lexer
{
    protected FieldLexer $fieldLexer;

    public function __construct(FieldLexer $fieldLexer)
    {
        $this->fieldLexer = $fieldLexer;
    }

    public function analyze(array $tokens): array
    {
        $registry = ['events' => []];

        if (empty($tokens['events'])) {
            return $registry;
        }

        foreach ($tokens['events'] as $name => $definition) {
            $event = $this->analyzeToken(
                $name,
                $definition,
                $tokens['namespace'] ?? null
            );

            $registry['events'][$event->getFqcn()] = $event;
        }

        return $registry;
    }

    protected function analyzeToken(string $name, array $definition, string $namespace = null)
    {
        $fqcn = $namespace ?? false;
        if ($fqcn && ! Str::contains($name, '\\')) {
            $name = '\\'.ltrim($fqcn, '\\').'\\'.$name;
        }

        $event = new Event($name);

        if (isset($definition['description'])) {
            $event->setDescription($definition['description']);
        }

        if (isset($definition['implements'])) {
            $event->setInterfaces($definition['implements']);
        }

        foreach ($definition['fieldsFrom'] ?? [] as $from) {
            $event->addFieldsFrom($from);
        }

        if (isset($definition['fields'])) {
            $fields = $this->fieldLexer->analyze([
                'fields' => $definition['fields'],
                'namespace' => $namespace,
            ]);
            $event->setFields($fields['fields']);
        }

        return $event;
    }
}
