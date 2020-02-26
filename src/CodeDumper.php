<?php

namespace Signifly\EventsourceGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class CodeDumper
{
    /**
     * Create a new Skeleton Instance.
     */
    public function __construct()
    {
        // constructor body
    }

    public function dumpAll(array $groups)
    {
        return array_map(fn ($group) => $this->dump($group), $groups);
    }

    public function dump(DefinitionGroup $group, bool $withHelpers = true)
    {
        $namespace = new PhpNamespace($group->namespace() ?? '');
//        $namespace->addUse('EventSauce\EventSourcing\Serialization\SerializablePayload');

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

        return $namespace;
    }

    private function hydrateComputed(ClassType $class, $computed)
    {
        /** @var Definition $comp */
        foreach ($computed as $comp) {
            $class->addMethod($comp->getName())
                ->setReturnType($comp->getType())
                ->addComment($comp->getDescription() ?? '')
                ->addBody($comp->getValue());
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
            ->setReturnType('self');
        $fromPayload->addParameter('payload')
            ->setType('array');
        $fromPayload->addBody("return new {$class->getName()}(");

        $helpers = [];

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
                $prop->addComment('@example '.$example);
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
            }
            $helpers[] = str_replace(['{type}', '{param}'], [$field->getType(), "{$example}"], trim($field->getDeserializer()));
        }

        $toPayload->addBody('];');
        $fromPayload->addBody(');');

        $class->addMethod('with')
            ->setStatic()
            ->addComment('@codeCoverageIgnore')
            ->setReturnType('self')
            ->addBody("return new {$class->getName()}(")
            ->addBody("\t".implode(",\n\t", $helpers))
            ->addBody(');');

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
        ];

        if (! $value = ($map[$field->getType()] ?? false)) {
            throw new \LogicException("Missing example value for {$field->getName()} of type {$field->getType()}");
        }

        return $value;
    }
}
