<?php

namespace Signifly\EventSourceGenerator;

use Illuminate\Support\Collection;
use Nette\PhpGenerator\PhpNamespace;

interface FileWriter
{
    /**
     * @var Collection&PhpNamespace[]
     * @return array Array of messages from the writer
     */
    public function writeCode(Collection $code): array;
}
