<?php

namespace Signifly\EventsourceGenerator\Generators;

use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Signifly\EventSourceGenerator\Contracts\Generator;
use Signifly\EventSourceGenerator\Models\Command;
use Signifly\EventSourceGenerator\Models\Computed;
use Signifly\EventSourceGenerator\Models\Event;
use Signifly\EventSourceGenerator\Models\Field;
use Signifly\EventSourceGenerator\Models\Model;
use Signifly\EventSourceGenerator\Models\Type;

class CommandGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    private array $tree;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $this->tree = $tree;

        /* @var Command $model */
        foreach ($tree['commands'] as $command) {
            $path = $this->getPath($command);

            if (! $this->files->exists(dirname($path))) {
                // todo: why..?
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put($path, $this->generateCommand($command));

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function getPath(Model $model)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($model->fullyQualifiedClassName()));

        return Blueprint::appPath().'/'.$path.'.php';
    }

    protected function generateCommand(Command $command)
    {
        $namespace = new PhpNamespace($command->namespace());
        // todo: default import + implements of EventSauce\SerializablePayload ??
//        $namespace->addUse('EventSauce\EventSourcing\Serialization\SerializablePayload');
        $class = $namespace->addClass($command->getName());
        foreach ($command->getInterfaces() as $interface) {
            $class->addImplement($this->resolveInterface($interface));
        }

        if ($command->getDescription()) {
            $class->addComment($command->getDescription());
        }

        $this->hydrateProperties($class, $this->buildFields(
            $command->getFieldsFrom(),
            $command->getFields()
        ));
        $this->hydrateComputed($class, $command->getComputed());
    }

    protected function resolveInterface($interface)
    {
        return $interface;
    }

    private function hydrateComputed(ClassType $class, array $computes)
    {
        /** @var Computed $compute */
        foreach ($computes as $compute) {
            $class->addMethod($compute->getName())
                ->setReturnType($this->resolveType($compute->getType()))
                ->addComment($compute->getDescription() ?? '')
                ->addBody($compute->getValue());
        }
    }

    private function hydrateProperties(ClassType $class, $fields)
    {
        $constructor = $class->addMethod('__construct');

        $toPayload = $class->addMethod('toPayload')->setReturnType('array');
        $toPayload->addBody('return [');

        $fromPayload = $class->addMethod('fromPayload')
            ->setStatic()
            ->setReturnType('self');
        $fromPayload->addParameter('payload')
            ->setType('array');
        $fromPayload->addBody("return new {$class->getName()}(");

        $helpers = [];

        $helpConstructor = [];
        $helpConstructorArgs = [];
        $helpConstructorVals = [];

        foreach ($fields as $field) {
            $constructor->addParameter($field->getName())
                ->setType($field->getType())
                ->setNullable($field->getNullable());
            $constructor->addBody("\$this->{$field->getName()} = \${$field->getName()};");

            $prop = $class->addProperty($field->getName())
                ->setType($field->getType())
                ->setVisibility('protected')
                ->setNullable($field->getNullable());

            if ($field->getDescription()) {
                $prop->addComment($field->getDescription());
            }

            foreach ($field->getExamples() as $example) {
                $prop->addComment('@example '.$this->makeExample($example));
            }

            $class->addMethod($attribute = $field->getName())
                ->setReturnType($field->getType())
                ->setReturnNullable($field->getNullable())
                ->setBody("return \$this->{$attribute};");

            $class->addMethod('with'.ucfirst($attribute))
                ->setReturnType('self')
                ->addComment('@codeCoverageIgnore')
                ->addBody('$clone = clone $this;')
                ->addBody("\$clone->{$attribute} = \${$attribute};")
                ->addBody('')
                ->addBody('return $clone;')
                ->addParameter($attribute)
                ->setType($field->getType())
                ->setNullable($field->getNullable());

            $serialize = $this->buildSerializer($field);
            $toPayload->addBody("\t'{$field->getName()}' => {$serialize},");

            $deserialize = $this->buildUnserializer($field);
            $fromPayload->addBody("\t{$deserialize},");

            if (! isset($field->getExamples()[0])) {
                $example = $this->makeExampleValue($field);
            } else {
                $example = $field->getExamples()[0];
                $isString = $field->getType() === 'string' || $field->getType() === '?string';
                if ($isString && ! Str::startsWith($example, ['"', "'"])) {
                    $example = "'".addslashes($example)."'";
                }
            }

            if ($example === null) {
                $helpConstructor[] = ucfirst($field->getName());
                $helpConstructorArgs[] = $field;
                $helpConstructorVals[] = '$'.$field->getName();
            } else {
                $example = str_replace('{type}', $field->getType(), $example);
                $helpConstructorVals[] = $helpers[] = str_replace(
                    ['{type}', '{param}'],
                    [$field->getType(), "{$example}"],
                    trim($field->getDeserializer())
                );
            }
        }

        if (! empty($helpConstructor)) {
            $help = $class->addMethod('with'.implode('And', $helpConstructor))
                ->setStatic()
                ->addComment('@codeCoverageIgnore')
                ->setReturnType('self')
                ->addBody("return new {$class->getName()}(")
                ->addBody("\t".implode(",\n\t", $helpConstructorVals))
                ->addBody(');');
            foreach ($helpConstructorArgs as $args) {
                $help->addParameter($args->getName())->setType($args->getType())->setNullable($args->getNullable());
            }
        } else {
            $class->addMethod('with')
                ->setStatic()
                ->addComment('@codeCoverageIgnore')
                ->setReturnType('self')
                ->addBody("return new {$class->getName()}(")
                ->addBody("\t".implode(",\n\t", $helpers))
                ->addBody(');');
        }

        $toPayload->addBody('];');
        $fromPayload->addBody(');');

        // todo: ::with() with missing example values?
        // todo: computed with params?
        // todo: rotate example values?
    }

    protected function makeExample($example)
    {
        if (Str::startsWith('!!', $example)) {
            $example = eval($example);
        }

        return (string) $example;
    }

    protected function buildFields(array $fieldsFrom = [], array $fields = [])
    {
        $parents = [];
        foreach ($fieldsFrom ?? [] as $field) {
            $parent = $this->resolveFieldsFrom([$field['name']]);
            if (! $parent) {
                throw new \LogicException("The fields from '{$field['name']}' could not be found in the current namespace for {$this->name}.");
            }

            /** @var string[] $except */
            $except = $field['except'] ?? [];
            $parent = array_filter($parent, function (Field $field) use ($except) {
                return ! in_array($field->getName(), $except);
            });

            $parents = [...$parents, ...$parent];
        }

        return [
            ...$parents,
            ...$fields,
        ];
    }

    protected function buildUnserializer($field)
    {
        $deserialize = str_replace(
            ['{type}', '{param}'],
            [$field->getType(), "\$payload['{$field->getName()}']"],
            trim($field->getDeserializer())
        );
        if ($field->getNullable()) {
            $deserialize = "(\$payload['{$field->getName()}'] ?? null) === null ? null : ".$deserialize;
        }

        return $deserialize;
    }

    protected function buildSerializer($field)
    {
        // todo: snake case / camel case deserialize/serialize payload
        $serialize = $this->makeSubstitutions($field);
        if ($field->getNullable()) {
            $serialize = "\$this->{$field->getName()} === null ? null : ".$serialize;
        }

        return $serialize;
    }

    protected function makeSubstitutions(string $text, Type $type)
    {
        $serialize = str_replace(
            ['{type}', '{param}'],
            [$type->getType(), "\$this->{$type->getName()}"],
            trim($text)
        );

        return $serialize;
    }
}
