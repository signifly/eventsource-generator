<?php

namespace Signifly\EventSourceGenerator;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class FilePerNamespaceWriter implements FileWriter
{
    protected Collection $pathMap;
    protected string $fileName;
    protected string $composerPath;
    protected string $fallbackRootPath;

    // todo: refactor to Config value object if more arguments are required?
    public function __construct(
        string $fileName = 'generated.php',
        string $composerPath = 'composer.json',
        string $fallbackRootPath = 'app/'
    ) {
        $this->fileName = $fileName;
        $this->composerPath = $composerPath;
        $this->fallbackRootPath = $fallbackRootPath;
    }

    public function writeCode(Collection $code): array
    {
        return $code->reject(fn (PhpNamespace $namespace) => count($namespace->getClasses()) == 0)
            ->groupBy(fn (PhpNamespace $namespace) => $namespace->getName())
            ->mapWithKeys(function (Collection $namespaces) {
                /** @var PhpNamespace $first */
                $first = $namespaces->pop();
                $namespaces->each(function (PhpNamespace $namespace) use ($first) {
                    foreach ($namespace->getClasses() as $class) {
                        $first->add($class);
                    }
                });

                return [$first->getName() => (new PsrPrinter())->setTypeResolving(false)->printNamespace($first)];
            })
            ->map(function ($code, $namespace) {
                $path = $this->getFilepath($namespace);
                $this->ensurePathExists(dirname($path));
                $outputFile = $this->getFilepath($namespace);
                $wrote = file_put_contents($path, "<?php\n\n".$code) !== false;

                return $wrote
                    ? "Written code to `{$outputFile}`"
                    : "Failed to write code to `{$outputFile}`";
            })
            ->all();
    }

    /** @see https://stackoverflow.com/a/6205454 */
    protected function ensurePathExists($path)
    {
        if (is_dir($path)) {
            return true;
        }
        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
        $return = $this->ensurePathExists($prev_path);

        return ($return && is_writable($prev_path)) ? mkdir($path) : false;
    }

    public function getFilepath($namespace)
    {
        $root = $this->getRootPath($namespace);
        $remove = array_flip($this->pathMap->all());
        $folder = str_replace('\\', '/', str_replace($remove, '', $namespace));

        return $root.$folder.DIRECTORY_SEPARATOR.$this->fileName;
    }

    public function getRootPath($namespace)
    {
        if (! isset($this->pathMap)) {
            $this->parseComposer($this->composerPath);
        }

        return $this->pathMap->first(function ($_, $path) use ($namespace) {
            return Str::startsWith($namespace, $path);
        }, $this->fallbackRootPath);
    }

    public function parseComposer(string $composerPath): void
    {
        $composerContents = json_decode(file_get_contents($composerPath), true);

        $this->pathMap = collect(data_get($composerContents, 'autoload.psr-4'))
            ->mapWithKeys(function ($path, $ns) {
                return [trim($ns, '\\') => $path];
            })
            ->sortByDesc(fn ($value, $key) => substr_count($key, '\\'));
    }
}
