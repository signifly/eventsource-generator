<?php

namespace Signifly\EventsourceGenerator\Tests;

use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\TestCase;
use Signifly\EventsourceGenerator\CodeDumper;
use Signifly\EventsourceGenerator\YamlDefinitionLoader;

class ExampleTest extends TestCase
{
    /** @test */
    public function true_is_true()
    {
        $loader = new YamlDefinitionLoader();
        $groups = $loader->loadFiles([
            __DIR__.'/Fixtures/types.yml',
            __DIR__.'/Fixtures/inheritCommand.yml',
            __DIR__.'/Fixtures/example.yml',
        ]);

        $dumper = new CodeDumper();

        $classes = array_map(function ($namespace) {
            return (new PsrPrinter())->printNamespace($namespace);
        }, $dumper->dumpAll($groups));

//        file_put_contents(
//            'test_class.php',
//            "<?php\n\n".implode("\n", $classes)
//        );
//        dd();

        $this->assertNotNull($classes);
    }
}
