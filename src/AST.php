<?php

namespace Signifly\EventSourceGenerator;

use Signifly\EventSourceGenerator\Models\Command;
use Signifly\EventSourceGenerator\Models\Event;
use Signifly\EventSourceGenerator\Models\Field;
use Signifly\EventSourceGenerator\Models\Model;
use Signifly\EventSourceGenerator\Models\Type;

class AST
{
    protected array $tree = [];

    public function __construct()
    {
    }

    public function commands(?string $name = null)
    {
        $commands = $this->tree['commands'] ?? [];

        return $name ? $commands[$name] : $commands;
    }

    public function events(?string $name = null)
    {
        $events = $this->tree['events'] ?? [];

        return $name ? $events[$name] : $events;
    }

    public function addAnalysis(array $tree)
    {
        $this->tree = array_merge_recursive($this->tree, $tree);
    }

    public function resolveField($fieldName): Field
    {
        return $this->resolveModel('fields', $fieldName);
    }

    public function resolveType($typeName): Type
    {
        return $this->resolveModel('types', $typeName);
    }

    public function resolveModel($type, $name)
    {
        return collect($this->tree[$type])
            ->first(fn (Model $model) => $model->getName() == $name);
    }

    public function resolveFieldsFor(Event $model)
    {
        $inherited = array_values(
            array_map(function ($field) {
                return $this->resolveFieldsFrom($field['name'], $field['except'] ?? []);
            }, $model->getFieldsFrom())
        );

        return collect([
            ...$inherited,
            ...array_values($model->getFields()),
        ])
            ->flatten()
            ->mapWithKeys(function (Field $field) {
                return [$field->getName() => $field];
            })
            ->all();
    }

    public function resolveFieldsFrom(string $parentName, array $except = [])
    {
        $parent = $this->tree['commands'][$parentName]
            ?? $this->tree['events'][$parentName]
            ?? null;

        if (! $parent) {
            // try other namespace?
            return [];
        }

        /** @var Command|Event $parent */
        $parents = [];
        foreach ($parent->getFieldsFrom() ?? [] as $field) {
            $parents = [
                $this->resolveFieldsFrom($field['name'], $field['except'] ?? []),
                ...$parents,
            ];
        }

        $fields = [
            ...array_values($this->resolveFieldsFor($parent)),
            ...$parents,
        ];

        return collect($fields)
            ->flatten()
            ->filter(fn (Field $field) => ! in_array($field->getName(), $except))
            ->all();
    }
}
