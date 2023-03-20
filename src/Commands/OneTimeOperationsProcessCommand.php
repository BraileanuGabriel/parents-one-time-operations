<?php

namespace EBS\ParentsOneTimeOperations\Commands;

use EBS\ParentsOneTimeOperations\Jobs\OneTimeOperationProcessJob;
use EBS\ParentsOneTimeOperations\Models\Operation;
use EBS\ParentsOneTimeOperations\OneTimeOperationFile;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationsProcessCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:process
                            {name? : Name of specific operation}
                            {--test : Process operation without tagging it as processed, so you can call it again}
                            {--async : Ignore setting in operation and process all operations asynchronously}
                            {--sync : Ignore setting in operation and process all operations synchronously}';

    protected $description = 'Process all unprocessed one-time operations';

    protected $forceAsync = false;

    protected $forceSync = false;

    public function handle(): int
    {
        $this->displayTestmodeWarning();

        $this->forceAsync = (bool) $this->option('async');
        $this->forceSync = (bool) $this->option('sync');

        if ($this->forceAsync && $this->forceSync) {
            $this->components->error('Abort! Process either with --sync or --async.');

            return self::FAILURE;
        }

        if ($operationName = $this->argument('name')) {
            return $this->proccessSingleOperation($operationName);
        }

        return $this->processNextOperations();
    }

    protected function proccessSingleOperation(string $providedOperationName): int
    {
        $providedOperationName = str($providedOperationName)->rtrim('.php')->toString();

        try {
            if ($operationModel = OneTimeOperationManager::getModelByName($providedOperationName)) {
                return $this->processOperationModel($operationModel);
            }

            $operationsFile = OneTimeOperationManager::getOperationFileByName($providedOperationName);

            return $this->processOperationFile($operationsFile);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function processOperationFile(OneTimeOperationFile $operationFile): int
    {
        $this->components->task($operationFile->getOperationName(), function () use ($operationFile) {
            $this->processOperation($operationFile);
            $this->storeOperation($operationFile);
        });

        $this->newLine();
        $this->components->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function processOperationModel(Operation $operationModel): int
    {
        if (! $this->components->confirm('Operation was processed before. Process it again?')) {
            $this->components->info('Operation aborted');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Processing operation %s.', $operationModel->name));

        $this->components->task($operationModel->name, function () use ($operationModel) {
            $operationFile = OneTimeOperationManager::getOperationFileByModel($operationModel);

            $this->processOperation($operationFile);
            $this->storeOperation($operationFile);
        });

        $this->newLine();
        $this->components->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function processNextOperations(): int
    {
        $unprocessedOperationFiles = OneTimeOperationManager::getUnprocessedOperationFiles();

        if ($unprocessedOperationFiles->isEmpty()) {
            $this->components->info('No operations to process.');

            return self::SUCCESS;
        }

        $this->components->info('Processing operations.');

        foreach ($unprocessedOperationFiles as $operationFile) {
            $this->components->task($operationFile->getOperationName(), function () use ($operationFile) {
                $this->processOperation($operationFile);
                $this->storeOperation($operationFile);
            });
        }

        $this->newLine();
        $this->components->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function storeOperation(OneTimeOperationFile $operationFile): void
    {
        if ($this->testModeEnabled()) {
            return;
        }

        Operation::storeOperation($operationFile->getOperationName(), $this->isAsyncMode($operationFile));
    }

    protected function processOperation(OneTimeOperationFile $operationFile)
    {
        if ($this->isAsyncMode($operationFile)) {
            OneTimeOperationProcessJob::dispatch($operationFile->getOperationName());
        } else {
            OneTimeOperationProcessJob::dispatchSync($operationFile->getOperationName());
        }
    }

    protected function testModeEnabled(): bool
    {
        return $this->option('test');
    }

    protected function displayTestmodeWarning(): void
    {
        if ($this->testModeEnabled()) {
            $this->components->warn('Testmode! Operation won\'t be tagged as `processed`');
        }
    }

    protected function isAsyncMode(OneTimeOperationFile $operationFile): bool
    {
        if ($this->forceAsync) {
            return true;
        }

        if ($this->forceSync) {
            return false;
        }

        return $operationFile->getClassObject()->isAsync();
    }
}
