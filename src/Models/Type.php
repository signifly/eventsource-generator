<?php

namespace Signifly\EventSourceGenerator\Models;

class Type extends Model
{
    protected string $template;
    protected string $type;
    protected bool $nullable = false;
    protected string $description;
    protected string $example;
    protected string $serializer;
    protected string $unserializer;

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    // Used in tests
    public function getNullable(): bool
    {
        return $this->isNullable();
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getExample(): string
    {
        return $this->example;
    }

    public function setExample(string $example): self
    {
        $this->example = $example;

        return $this;
    }

    public function getSerializer(): string
    {
        return $this->serializer;
    }

    /**
     * @todo Add validation?
     */
    public function setSerializer(string $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function getUnserializer(): string
    {
        return $this->unserializer;
    }

    /**
     * @todo Add validation?
     */
    public function setUnserializer(string $unserializer): self
    {
        $this->unserializer = $unserializer;

        return $this;
    }
}
