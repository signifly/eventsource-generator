<?php

namespace Signifly\EventSourceGenerator\Tests;

use PHPUnit\Framework\TestCase;
use Signifly\EventSourceGenerator\FilePerNamespaceWriter;

class FilePerNamespaceWriterTest extends TestCase
{
    private FilePerNamespaceWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = new FilePerNamespaceWriter();

        $this->markTestIncomplete('Need to be rewritten to use a dummy composer file.');
    }

    /** @test */
    public function it_finds_app_root_path()
    {
        $this->assertEquals('app/App/', $this->writer->getRootPath('App'));
    }

    /** @test */
    public function it_finds_domain_root_path()
    {
        $this->assertEquals('app/Domain/', $this->writer->getRootPath('Domain'));
    }

    /** @test */
    public function it_defaults_to_app_root_for_non_existant_domains()
    {
        $this->assertEquals('app/App/', $this->writer->getRootPath('DoesntExist'));
    }

    /** @test */
    public function it_determines_app_domain_path()
    {
        $this->assertEquals('app/App/Models/Generated', $this->writer->getRootPath('App\\Models\\Generated'));
    }

    /** @test */
    public function it_determines_domain_domain_path()
    {
        $this->assertEquals('app/Domain/Products/Models/Generated', $this->writer->getRootPath('Domain\\Products\\Models\\Generated'));
    }

    /** @test */
    public function it_determines_test_file_path()
    {
        $namespace = 'Signifly\\EventSourceGenerator\\Tests\\Generated\\SimpleCode';

        $expectedPath = 'src/EventSourceGenerator/tests/Generated/SimpleCode/generated.php';
        $this->assertEquals($expectedPath, $this->writer->getFilepath($namespace));
    }

    /** @test */
    public function it_writes_a_simple_class()
    {
        $this->markTestSkipped('Should be passed a Collection of PhpNamespace instead');
        $namespace = 'Signifly\\EventSourceGenerator\\Tests\\Generated\\SimpleCode';
        $code = <<<CODE
namespace $namespace;

class SimpleTestClass {
    public int \$integer;
}
CODE;

        $path = $this->writer->getFilepath($namespace);
        $success = $this->writer->writeCode($code, $namespace);

        $expectedPath = 'src/EventSourceGenerator/tests/Generated/SimpleCode/generated.php';
        $this->assertTrue($success);
        $this->assertEquals($expectedPath, $path);

        require_once $expectedPath;

        $reflection = new \ReflectionClass($namespace.'\\SimpleTestClass');
        $this->assertCount(1, $reflection->getProperties());

        $prop = $reflection->getProperty('integer');
        $this->assertNotNull($prop);
        $this->assertEquals('int', $prop->getType());
    }
}
