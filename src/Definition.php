<?php

declare(strict_types=1);

namespace Signifly\EventsourceGenerator;

class Definition
{
    public const INTERFACE = 'interfaces';
    public const TYPE = 'types';
    public const FIELD = 'fields';
    public const COMMAND = 'commands';
    public const EVENT = 'events';

    private string $name;
    private string $group;

    private ?string $type = null;
    private ?string $field = null;
    private ?bool $nullable = null;
    private ?string $description = null;
    private ?string $serializer = null;
    private ?string $deserializer = null;
    private ?array $interfaces = null;
    private ?array $examples = null;
    private ?array $computed = null;
    private ?array $fieldsFrom = null;

    /** @var Definition[] Nested fields for commands and events */
    private array $fields = [];
    /** @var string|null Method body for computed field */
    private ?string $value = null;
    private DefinitionGroup $definitionGroup;

    public function __construct(string $name, string $group)
    {
        $this->name = $name;
        $this->group = $group;
    }

    public function setGroup(DefinitionGroup $definitionGroup)
    {
        $this->definitionGroup = $definitionGroup;
        if ($this->fields) {
            $this->fields = array_map(fn (self $definition) => $definition->setGroup($definitionGroup), $this->fields);
        }
        if ($this->computed) {
            $this->computed = array_map(fn (self $definition) => $definition->setGroup($definitionGroup), $this->computed);
        }

        return $this;
    }

    public function withType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function withField(string $field)
    {
        $this->field = $field;

        return $this;
    }

    public function withFields(self $field)
    {
        if (! in_array($this->group, [self::COMMAND, self::EVENT])) {
            throw new \InvalidArgumentException('Fields attribute is only supported for commands and events.');
        }
        $this->fields[] = $field;

        return $this;
    }

    public function withNullable(bool $value): self
    {
        $this->nullable = $value;

        return $this;
    }

    public function withFieldsFrom($otherType): self
    {
        $this->fieldsFrom[] = $otherType;

        return $this;
    }

    public function withInterface(string $interface): self
    {
        $this->interfaces[] = $interface;

        return $this;
    }

    public function withExample($example): self
    {
        $this->examples[] = $example;

        return $this;
    }

    public function withComputed($computed): self
    {
        $this->computed[] = $computed;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function withSerializer(string $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function withDeserializer(string $deserializer): self
    {
        $this->deserializer = $deserializer;

        return $this;
    }

    public function withValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): string
    {
        $t = $this->resolveField('type');
        if ($t === null) {
            $type = $this->type ?? $this->field;
            throw new \LogicException("Could not resolve type '{$type}' for field {$this->name}.");
        }
        $type = $this->definitionGroup->resolveType($t);
        if (! $type) {
            return $t;
        }

        if (is_object($type)) {
            return $type->getType();
        }

        return $type;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getNullable(): bool
    {
        return (bool) $this->resolveField('nullable') ?? false;
    }

    public function getDescription(): ?string
    {
        return $this->resolveField('description');
    }

    public function getSerializer(): string
    {
        return $this->resolveField('serializer') ?? '{param}';
    }

    public function getDeserializer(): string
    {
        return $this->resolveField('deserializer') ?? '{param}';
    }

    public function getInterfaces(): array
    {
        return $this->resolveField('interfaces') ?? [];
    }

    public function getExamples(): array
    {
        return array_map('trim', $this->resolveField('examples') ?? []);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getComputed(): array
    {
        return $this->computed ?? [];
    }

    public function getFieldsFrom(): array
    {
        return $this->fieldsFrom;
    }

    public function getFields(): array
    {
        $parents = [];
        foreach ($this->fieldsFrom ?? [] as $field) {
            $parent = $this->definitionGroup->resolveFieldsFrom($this->fieldsFrom);
            if (empty($parent)) {
                throw new \LogicException("The fields from '{$field}' could not be found in the current namespace for {$this->name}.");
            }

            $parents = [...$parents, ...$parent];
        }

        return [
            ...$parents,
            ...$this->fields,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    protected function resolveField($attribute)
    {
        if ($this->{$attribute} !== null) {
            return $this->{$attribute};
        }

        $method = 'get'.ucfirst($attribute);
        if ($this->field) {
            $field = $this->definitionGroup->resolveField($this->field);
            if ($field) {
                if (($value = $field->{$method}()) !== null) {
                    return $value;
                }

                $type = $this->definitionGroup->resolveType($field->type);
                if ($type && ($value = $type->{$method}()) !== null) {
                    return $value;
                }
            }
        }

        $lookup = $this->type ?: $this->field;
        if ($lookup === null) {
            return;
        }

        $type = $this->definitionGroup->resolveType($lookup);
        if (! $type) {
            return $this->{$attribute};
        }

        if (is_object($type)) {
            return $type->{$method}();
        }

        return $attribute === 'type' && TypeNormalizer::isNativeType($type)
            ? $type
            : null;
    }
}
