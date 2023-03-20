<?php

namespace EBS\ParentsOneTimeOperations;

use ErrorException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class OneTimeOperationCreator
{
    protected $operationsDirectory;

    protected $providedName;

    protected $operationName = '';

    public function __construct()
    {
        $this->operationsDirectory = OneTimeOperationManager::getDirectoryPath();
    }

    /**
     * @throws \Throwable
     */
    public static function createOperationFile(string $name): OneTimeOperationFile
    {
        $instance = new self();
        $instance->setProvidedName($name);

        return OneTimeOperationFile::make($instance->createFile());
    }

    /**
     * @throws \Throwable
     */
    public function createFile(): \SplFileInfo
    {
        $path = $this->getPath();
        $stub = $this->getStubFilepath();
        $this->ensureDirectoryExists();

        throw_if(File::exists($path), ErrorException::class, (array)sprintf('File already exists: %s', $path));

        File::put($path, $stub);

        return new \SplFileInfo($path);
    }

    protected function getPath(): string
    {
        return $this->operationsDirectory.DIRECTORY_SEPARATOR.$this->getOperationName();
    }

    protected function getStubFilepath(): string
    {
        return File::get(__DIR__.'/../stubs/one-time-operation.stub');
    }

    public function getOperationName(): string
    {
        if (! $this->operationName) {
            $this->initOperationName();
        }

        return $this->operationName;
    }

    protected function getDatePrefix(): string
    {
        return Carbon::now()->format('Y_m_d_His');
    }

    protected function initOperationName(): void
    {
        $this->operationName = $this->getDatePrefix().'_'.strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->providedName)).'.php';
    }

    protected function ensureDirectoryExists(): void
    {
        File::ensureDirectoryExists($this->operationsDirectory);
    }

    public function setProvidedName(string $providedName): void
    {
        $this->providedName = $providedName;
    }
}
