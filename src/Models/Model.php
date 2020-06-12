<?php

namespace Signifly\EventSourceGenerator\Models;

class Model
{
    private string $name;
    private string $namespace;

    public function __construct($name)
    {
        $this->name = class_basename($name);
        $this->namespace = trim(implode('\\', array_slice(explode('\\', str_replace('/', '\\', $name)), 0, -1)), '\\');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getFqcn()
    {
        return $this->name.$this->namespace;
    }
}
