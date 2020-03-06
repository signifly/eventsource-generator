<?php

namespace Signifly\EventSourceGenerator\Tests;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\CodeDumper;
use Signifly\EventSourceGenerator\YamlDefinitionLoader;

class SimpleTestCase extends TestCase
{
    protected $factoryDocBlock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factoryDocBlock = \phpDocumentor\Reflection\DocBlockFactory::createInstance();

        $loader = new YamlDefinitionLoader();
        $groups = $loader->loadFiles([
            __DIR__.'/Fixtures/types.yml',
            __DIR__.'/Fixtures/simpleCommands.yml',
        ]);

        $dumper = new CodeDumper();

        $classes = array_map(function ($namespace) {
            return (new PsrPrinter())->printNamespace($namespace);
        }, $dumper->dumpAll($groups));

        file_put_contents('test_file.php', "<?php\n\n".implode("\n", $classes));
        require_once 'test_file.php';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unlink('test_file.php');
    }
}
