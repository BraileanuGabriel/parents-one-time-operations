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
                            {--test : Process operation without tagging it as processed, so you can call it again}';

    protected $description = 'Process all unprocessed one-time operations';


    public function handle(): int
    {
        $this->displayTestmodeWarning();

        if ($operationName = $this->argument('name')) {
            return $this->proccessSingleOperation($operationName);
        }

        return $this->processNextOperations();
    }

    protected function proccessSingleOperation(string $providedOperationName): int
    {
        $providedOperationName = substr($providedOperationName, 0, strpos($providedOperationName, ".php"));

        try {
            if ($operationModel = OneTimeOperationManager::getModelByName($providedOperationName)) {
                return $this->processOperationModel($operationModel);
            }

            $operationsFile = OneTimeOperationManager::getOperationFileByName($providedOperationName);

            return $this->processOperationFile($operationsFile);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @throws \Throwable
     */
    protected function processOperationFile(OneTimeOperationFile $operationFile): int
    {
        $this->task($operationFile->getOperationName(), function () use ($operationFile) {
            $this->processOperation($operationFile);
            $this->storeOperation($operationFile);
        });

        $this->info("\n");
        $this->info('Processing finished.');

        return self::SUCCESS;
    }

    /**
     * @throws \Throwable
     */
    protected function processOperationModel(Operation $operationModel): int
    {
        if (! $this->confirm('Operation was processed before. Process it again?')) {
            $this->info('Operation aborted');

            return self::SUCCESS;
        }

        $this->info(sprintf('Processing operation %s.', $operationModel->name));

        $this->task($operationModel->name, function () use ($operationModel) {
            $operationFile = OneTimeOperationManager::getOperationFileByModel($operationModel);

            $this->processOperation($operationFile);
            $this->storeOperation($operationFile);
        });

        $this->info("\n");
        $this->info('Processing finished.');

        return self::SUCCESS;
    }

    /**
     * @throws \Throwable
     */
    protected function processNextOperations(): int
    {
        $unprocessedOperationFiles = OneTimeOperationManager::getUnprocessedOperationFiles();

        if ($unprocessedOperationFiles->isEmpty()) {
            $this->info('No operations to process.');

            return self::SUCCESS;
        }

        $this->info('Processing operations.');

        foreach ($unprocessedOperationFiles as $operationFile) {
            $this->task($operationFile->getOperationName(), function () use ($operationFile) {
                $this->processOperation($operationFile);
                $this->storeOperation($operationFile);
            });
        }

        $this->info("\n");
        $this->info('Processing finished.');

        return self::SUCCESS;
    }

    protected function storeOperation(OneTimeOperationFile $operationFile): void
    {
        if ($this->testModeEnabled()) {
            return;
        }

        Operation::storeOperation($operationFile->getOperationName());
    }

    protected function processOperation(OneTimeOperationFile $operationFile)
    {
        OneTimeOperationProcessJob::dispatch($operationFile->getOperationName());
    }

    protected function testModeEnabled(): bool
    {
        return $this->option('test');
    }

    protected function displayTestmodeWarning(): void
    {
        if ($this->testModeEnabled()) {
            $this->warn('Testmode! Operation won\'t be tagged as `processed`');
        }
    }
}
