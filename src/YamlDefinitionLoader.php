<?php

declare(strict_types=1);

namespace Signifly\EventsourceGenerator;

use function file_get_contents;
use function in_array;
use InvalidArgumentException;
use function is_array;
use function is_string;
use function pathinfo;
use const PATHINFO_EXTENSION;
use Symfony\Component\Yaml\Yaml;

class YamlDefinitionLoader
{
    private DefinitionGroup $general;
    private $groups = [];

    public function __construct()
    {
        $this->general = new DefinitionGroup();
    }

    public function loadFiles(array $fileNames): array
    {
        foreach ($fileNames as $file) {
            $this->groups[] = $this->load($file);
        }

        foreach ($this->groups as $group) {
            $group->setInterfaces($this->general->interfaces());
            foreach ($this->general->fieldsAndTypes() as $entry) {
                $group->addDefinition($entry);
            }
        }

        return $this->groups;
    }

    public function canLoad(string $filename): bool
    {
        return in_array(pathinfo($filename, PATHINFO_EXTENSION), ['yaml', 'yml']);
    }

    protected function load(string $filename, DefinitionGroup $definitionGroup = null): DefinitionGroup
    {
        /** @var string|bool $fileContents */
        $fileContents = file_get_contents($filename);

        if (! is_string($fileContents) || empty($fileContents)) {
            throw new InvalidArgumentException("File {$filename} does not contain anything");
        }

        $parsed = Yaml::parse($fileContents);
        if (! is_array($parsed)) {
            throw new InvalidArgumentException('The definition is incorrectly formatted');
        }

        $definitionGroup = $definitionGroup ?: new DefinitionGroup();

        if (isset($parsed['namespace'])) {
            $definitionGroup->withNamespace($parsed['namespace']);
        } elseif (isset($parsed['events']) || isset($parsed['commands'])) {
            throw new \LogicException("The 'namespace' field should be set when defining events or commands. The field is missing in {$filename}");
        }

        foreach ($parsed['interfaces'] ?? [] as $alias => $actual) {
            $this->general->defineInterface($alias, $actual);
        }

        foreach (['types', 'fields'] as $fieldType) {
            foreach ($this->parseFields($parsed[$fieldType] ?? [], $fieldType) as $field) {
                $this->general->addDefinition($field);
            }
        }

        $defaultInterfaces = [
            'commands' => [
                '\EventSauce\EventSourcing\Serialization\SerializablePayload', // todo: config value
            ],
            'events' => [
                '\EventSauce\EventSourcing\Serialization\SerializablePayload',
            ],
        ];

        foreach (['commands', 'events'] as $fieldType) {
            foreach ($parsed[$fieldType] ?? [] as $fieldName => $declaration) {
                $def = new Definition($fieldName, $fieldType);

                foreach ((array) ($declaration['implements'] ?? []) + $defaultInterfaces[$fieldType] as $interface) {
                    $def->withInterface($interface);
                }
                foreach ((array) ($declaration['fieldsFrom'] ?? []) as $name) {
                    $def->withFieldsFrom($name);
                }
                if (isset($declaration['description'])) {
                    $def->withDescription($declaration['description']);
                }
                foreach ($this->parseFields($declaration['fields'] ?? [], 'commands') as $field) {
                    $def->withFields($field);
                }
                foreach ($this->parseFields($declaration['computed'] ?? [], 'commands') as $field) {
                    $def->withComputed($field);
                }

                $definitionGroup->addDefinition($def);
            }
        }

//        foreach ($definitions as $definition) {
//            $definitionGroup->addDefinition($definition);
//        }

        return $definitionGroup;
    }

    private function parseFields(array $fields, string $fieldType)
    {
        $definitions = [];
        foreach ($fields as $fieldName => $declaration) {
            $definition = new Definition($fieldName, $fieldType);

            // if 'field' is set, it inherits
            $fields = $declaration['fields'] ?? ['fields' => $declaration];

            foreach ($fields as $key => $value) {
                if (! is_array($value)) {
                    // fieldName: fieldType format.. fallback to fieldName as type if undefined
                    $value = ['field' => $value ?? $fieldName];
                }
                foreach ((array) $value as $k => $v) {
                    $method = 'with'.ucfirst($k);
                    foreach ((array) $v as $item) {
                        $definition->$method($item);
                    }
                }
            }

            $definitions[] = $definition;
        }

        return $definitions;
    }
}
