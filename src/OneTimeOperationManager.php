<?php

namespace EBS\ParentsOneTimeOperations;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use SplFileInfo;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use EBS\ParentsOneTimeOperations\Models\Operation;

class OneTimeOperationManager
{
    /**
     * @return Collection<OneTimeOperationFile>
     */
    public static function getUnprocessedOperationFiles(): Collection
    {
        $operationFiles = self::getUnprocessedFiles();

        return $operationFiles->map(function (SplFileInfo $file) {
            return OneTimeOperationFile::make($file);
        });
    }

    /**
     * @return Collection<SplFileInfo>
     */
    public static function getUnprocessedFiles(): Collection
    {
        $allOperationFiles = self::getAllFiles();

        return $allOperationFiles->filter(function (SplFileInfo $operationFilepath) {
            $operation = self::getOperationNameFromFilename($operationFilepath->getBasename());

            return Operation::whereName($operation)->doesntExist();
        });
    }

    /**
     * @return Collection<SplFileInfo>
     */
    public static function getAllFiles(): Collection
    {
        try {
            return collect(File::files(self::getDirectoryPath()));
        } catch (DirectoryNotFoundException $e) {
            return collect();
        }
    }

    public static function getClassObjectByName(string $operationName): OneTimeOperation
    {
        $filepath = self::pathToFileByName($operationName);

        return File::getRequire($filepath);
    }

    public static function getModelByName(string $operationName): ?Operation
    {
        return Operation::whereName($operationName)->first();
    }

    /**
     * @throws \Throwable
     */
    public static function getOperationFileByModel(Operation $operationModel): OneTimeOperationFile
    {
        $filepath = self::pathToFileByName($operationModel->name);

        throw_unless(File::exists($filepath), FileNotFoundException::class);

        return OneTimeOperationFile::make(new SplFileInfo($filepath));
    }

    /**
     * @throws \Throwable
     */
    public static function getOperationFileByName(string $operationName): OneTimeOperationFile
    {
        $filepath = self::pathToFileByName($operationName);

        throw_unless(File::exists($filepath), FileNotFoundException::class, (array)sprintf('File %s does not exist', self::buildFilename($operationName)));

        return OneTimeOperationFile::make(new SplFileInfo($filepath));
    }

    public static function pathToFileByName(string $operationName): string
    {
        return self::getDirectoryPath().self::buildFilename($operationName);
    }

    public static function getDirectoryName(): string
    {
        return database_path(Config::get('one-time-operations.directory'));
    }

    public static function getDirectoryPath(): string
    {
        return self::getDirectoryName() . '/';
    }

    public static function getOperationNameFromFilename(string $filename): string
    {
        return substr($filename, 0, strpos($filename, ".php"));
    }

    public static function getTableName(): string
    {
        return Config::get('one-time-operations.table', 'operations'); // @TODO
    }

    public static function buildFilename($operationName): string
    {
        return $operationName.'.php';
    }
}
