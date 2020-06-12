<?php

namespace Signifly\EventSourceGenerator\Models;

class Field extends Type
{
    protected string $field;

    public static function fromType(Type $type)
    {
        $self = new self($type->getName());
        foreach (get_object_vars($type) as $key => $value) {
            $self->$key = $value;
        }

        return $self;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function setField(string $field): void
    {
        $this->field = $field;
    }
}
