<?php

namespace Signifly\EventSourceGenerator;

use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class CodeDumper
{
    private string $actionOnMissingExample;

    public function __construct(string $actionOnMissingExample = 'ignore')
    {
        $this->actionOnMissingExample = $actionOnMissingExample;
    }

    public function dumpAll(array $groups)
    {
        // todo: group by namespace if multiple files have the same namespace
        return array_map(fn ($group) => $this->dump($group), $groups);
    }

    public function dump(DefinitionGroup $group, bool $withHelpers = true)
    {
        $namespace = new PhpNamespace($group->namespace() ?? '');
        $namespace->addUse('EventSauce\EventSourcing\Serialization\SerializablePayload');

        foreach ([...$group->commands(), ...$group->events()] as $command) {
            $class = $namespace->addClass($command->getName());
            foreach ($command->getInterfaces() as $interface) {
                $class->addImplement($group->resolveInterface($interface));
            }
//            $class->addImplement('SerializablePayload');

            if ($command->getDescription()) {
                $class->addComment($command->getDescription());
            }

            $this->hydrateProperties($class, $command->getFields());
            $this->hydrateComputed($class, $command->getComputed());
        }

        foreach ($group->fieldsAndTypes() as $fieldsOrType) {
            if (TypeNormalizer::isNativeType($fieldsOrType->getType())) {
                continue;
            }

            $namespace->addUse($fieldsOrType->getType());
        }

        return $namespace;
    }

    private function hydrateComputed(ClassType $class, $computed)
    {
        /** @var Definition $comp */
        foreach ($computed as $comp) {
            $class->addMethod($comp->getName())
                ->setReturnType($comp->getType())
                ->addComment($comp->getDescription() ?? '')
                ->addBody(str_replace('{type}', $comp->getType(), $comp->getValue()));
        }
    }

    /**
     * @param ClassType $class
     * @param Definition[] $fields
     */
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
                $prop->addComment('@example '.((string) $example));
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

            // todo: snake case / camel case deserialize/serialize payload
            $serialize = str_replace(['{type}', '{param}'], [$field->getType(), "\$this->{$field->getName()}"], trim($field->getSerializer()));
            if ($field->getNullable()) {
                $serialize = "\$this->{$field->getName()} === null ? null : ".$serialize;
            }
            $toPayload->addBody("\t'{$field->getName()}' => {$serialize},");

            $deserialize = str_replace(
                ['{type}', '{param}'],
                [$field->getType(), "\$payload['{$field->getName()}']"],
                trim($field->getDeserializer())
            );
            if ($field->getNullable()) {
                $deserialize = "(\$payload['{$field->getName()}'] ?? null) === null ? null : ".$deserialize;
            }
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

    private function makeExampleValue(Definition $field)
    {
        if ($field->getNullable()) {
            return 'null';
        }

        $map = [
            'string' => "'Lorem ipsum'",
            'int' => 5,
            'float' => 13.37,
            'bool' => 1,
        ];

        if (! isset($map[$field->getType()])) {
            if (in_array($this->actionOnMissingExample, ['warn', 'error'])) {
                $exception = new \LogicException("Missing example value for {$field->getName()} of type {$field->getType()}");
                if ($this->actionOnMissingExample === 'error') {
                    throw $exception;
                }

                dump($exception->getMessage());
            }

            return;
        }

        return $map[$field->getType()];
    }
}
