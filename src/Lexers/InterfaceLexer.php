<?php

namespace Signifly\EventSourceGenerator\Lexers;

use Illuminate\Support\Str;
use Signifly\EventSourceGenerator\Contracts\Lexer;

class InterfaceLexer implements Lexer
{
    public function analyze(array $tokens): array
    {
        $registry = ['interfaces' => []];

        if (empty($tokens['interfaces'])) {
            return $registry;
        }

        foreach ($tokens['interfaces'] as $alias => $concrete) {
            $fqcn = $tokens['namespace'] ?? false;
            if ($fqcn && ! Str::contains($alias, '\\')) {
                $alias = '\\'.ltrim($fqcn, '\\').'\\'.$alias;
            }
            if (! Str::startsWith($concrete, '\\') && $fqcn) {
                $concrete = '\\'.ltrim($fqcn, '\\').'\\'.$concrete;
            }

            $registry['interfaces'][$alias] = $concrete ?? $alias;
        }

        return $registry;
    }
}
