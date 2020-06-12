<?php

namespace Signifly\EventSourceGenerator;

use Signifly\EventSourceGenerator\Contracts\Generator;
use Signifly\EventSourceGenerator\Contracts\Lexer;

class EventsourceGenerator
{
    private $lexers = [];
    private $generators = [];

    public static function relativeNamespace(string $fullyQualifiedClassName)
    {
        $namespace = config('blueprint.namespace').'\\';
        $reference = ltrim($fullyQualifiedClassName, '\\');

        if (Str::startsWith($reference, $namespace)) {
            return Str::after($reference, $namespace);
        }

        return $reference;
    }

    public static function appPath()
    {
        return str_replace('\\', '/', config('blueprint.app_path'));
    }

    public function parse($content)
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        return Yaml::parse($content);
    }

    public function analyze(array $tokens)
    {
        $registry = [
            'models' => [],
            'controllers' => [],
        ];

        foreach ($this->lexers as $lexer) {
            $registry = array_merge($registry, $lexer->analyze($tokens));
        }

        return $registry;
    }

    public function generate(array $tree): array
    {
        $components = [];

        foreach ($this->generators as $generator) {
            $components = array_merge_recursive($components, $generator->output($tree));
        }

        return $components;
    }

    public function dump(array $generated)
    {
        return Yaml::dump($generated);
    }

    public function registerLexer(Lexer $lexer)
    {
        $this->lexers[] = $lexer;
    }

    public function registerGenerator(Generator $generator)
    {
        $this->generators[] = $generator;
    }

    public function swapGenerator(string $concrete, Generator $generator)
    {
        foreach ($this->generators as $key => $registeredGenerator) {
            if (get_class($registeredGenerator) === $concrete) {
                unset($this->generators[$key]);
            }
        }

        $this->registerGenerator($generator);
    }
}
