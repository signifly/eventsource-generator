<?php

namespace Signifly\EventSourceGenerator\Lexers;

use Signifly\EventSourceGenerator\Models\Command;

class CommandLexer extends EventLexer
{
    protected ComputedLexer $computedLexer;

    public function __construct(FieldLexer $fieldLexer, ComputedLexer $computedLexer)
    {
        parent::__construct($fieldLexer);
        $this->computedLexer = $computedLexer;
    }

    public function analyze(array $tokens): array
    {
        $registry = ['commands' => []];

        if (empty($tokens['commands'])) {
            return $registry;
        }

        foreach ($tokens['commands'] as $name => $definition) {
            $event = $this->analyzeToken(
                $name,
                $definition,
                $tokens['namespace'] ?? null
            );

            $command = Command::fromEvent($event);

            foreach ($definition['computed'] ?? [] as $method => $body) {
                $command->addComputed($method, $this->computedLexer->analyze($body)[0]);
            }

            $registry['commands'][$command->getFqcn()] = $command;
        }

        return $registry;
    }
}
