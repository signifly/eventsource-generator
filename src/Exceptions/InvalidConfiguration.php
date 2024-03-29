<?php

namespace Signifly\EventSourceGenerator\Exceptions;

use Exception;

class InvalidConfiguration extends Exception
{
    public static function definitionFileDoesNotExist(string $definitionFile): self
    {
        return new static("The code generation definition file specified in the config file (`{$definitionFile}`) does not exist.");
    }
}
