<?php

declare(strict_types=1);

namespace Signifly\EventsourceGenerator;

use function array_key_exists;
use EventSauce\EventSourcing\PointInTime;
use OutOfBoundsException;

class DefinitionGroup
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var array
     */
    private $typeSerializer = [
        'string' => '({type}) {param}',
        'array' => '({type}) {param}',
        'integer' => '({type}) {param}',
        'int' => '({type}) {param}',
        'bool' => '({type}) {param}',
        'float' => '({type}) {param}',
    ];

    /**
     * @var array
     */
    private $typeDeserializer = [
        'string' => '({type}) {param}',
        'array' => '({type}) {param}',
        'integer' => '({type}) {param}',
        'int' => '({type}) {param}',
        'bool' => '({type}) {param}',
        'float' => '({type}) {param}',
    ];

    /**
     * @var string[]
     */
    private $interfaces = [];

    private $definitions = [];

    public function __construct()
    {
//        $this->typeSerializer(PointInTime::class, '{param}->toString()');
//        $this->typeDeserializer(PointInTime::class, '{type}::fromString({param})');
    }

    public static function create(string $namespace): self
    {
        return (new self())->withNamespace($namespace);
    }

    public function withNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function addDefinition(Definition $definition)
    {
        return $this->definitions[] = $definition->setGroup($this);
    }

    /**
     * @return Definition[]
     */
    public function commands(): array
    {
        return array_filter(
            $this->definitions,
            fn (Definition $definition) => $definition->getGroup() === Definition::COMMAND
        );
    }

    /**
     * @return Definition[]
     */
    public function events(): array
    {
        return array_filter(
            $this->definitions,
            fn (Definition $definition) => $definition->getGroup() === Definition::EVENT
        );
    }

    public function namespace(): ?string
    {
        return $this->namespace;
    }

    public function defineInterface(string $alias, string $interfaceName): void
    {
        $this->interfaces[$alias] = $interfaceName;
    }

    public function interfaces()
    {
        return $this->interfaces;
    }

    public function setInterfaces($interfaces)
    {
        $this->interfaces = $interfaces;
    }

    public function fieldsAndTypes()
    {
        return array_filter(
            $this->definitions,
            fn (Definition $definition) => in_array($definition->getGroup(), [Definition::FIELD, Definition::TYPE])
        );
    }

    public function resolveInterface(string $alias): string
    {
        if (interface_exists($alias) || $alias[0] == '\\') {
            return $alias;
        }

        if (! array_key_exists($alias, $this->interfaces)) {
            throw new OutOfBoundsException("Interface not registered for alias ${alias}.");
        }

        return $this->interfaces[$alias];
    }

    public function resolveType(string $type)
    {
        if (isset($this->typeSerializer[$type])) {
            // native php type
            return $type;
        }

        $types = array_filter(
            $this->definitions,
            fn (Definition $definition) => $definition->getGroup() === Definition::TYPE
        );

        return array_values(
            array_filter(
                $types,
                fn (Definition $definition) => $definition->getName() == $type
            )
        )[0] ?? null;
    }

    public function resolveField(string $field)
    {
        $fields = array_filter(
            $this->definitions,
            fn (Definition $definition) => $definition->getGroup() === Definition::FIELD
        );

        return array_values(
            array_filter(
                $fields,
                fn (Definition $definition) => $definition->getName() == $field
            )
        )[0] ?? null;
    }

    public function resolveFieldsFrom($name)
    {
        if ($name === null || empty($name)) {
            return [];
        }

        $definitions = array_filter(
            $this->definitions,
            fn (Definition $definition) => in_array($definition->getGroup(), [Definition::EVENT, Definition::COMMAND])
        );

        return array_values(
                array_map(fn (Definition $def) => $def->getFields(),
                array_filter(
                    $definitions,
                    fn (Definition $definition) => in_array($definition->getName(), (array) $name)
                )
            )
        )[0] ?? [];
    }
}
