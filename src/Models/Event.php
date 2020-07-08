<?php

namespace Signifly\EventSourceGenerator\Models;

class Event extends Model
{
    protected array $interfaces = [];
    protected array $fieldsFrom = [];
    protected array $fields = [];

    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function setInterfaces(array $interfaces): self
    {
        $this->interfaces = $interfaces;

        return $this;
    }

    public function getFieldsFrom(): array
    {
        return $this->fieldsFrom;
    }

    public function setFieldsFrom(array $fieldsFrom): self
    {
        $this->fieldsFrom = $fieldsFrom;

        return $this;
    }

    // todo: Add validation for format?
    public function addFieldsFrom(array $fieldsFrom): self
    {
        $this->fieldsFrom[] = $fieldsFrom;

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }
}
