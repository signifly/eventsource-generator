<?php


namespace Signifly\EventSourceGenerator\Contracts;


interface Lexer
{
    public function analyze(array $tokens): array;
}
