<?php

namespace Signifly\EventSourceGenerator\Models;

class Command extends Event
{
    protected array $computed = [];

    public static function fromEvent(Event $event)
    {
        $self = new self($event->getName());
        foreach (get_object_vars($event) as $key => $value) {
            $self->$key = $value;
        }

        return $self;
    }

    public function getComputed(): array
    {
        return $this->computed;
    }

    public function setComputed(array $computed): self
    {
        $this->computed = $computed;

        return $this;
    }

    public function addComputed(string $name, array $computed): self
    {
        $this->computed[$name] = $computed;

        return $this;
    }
}
