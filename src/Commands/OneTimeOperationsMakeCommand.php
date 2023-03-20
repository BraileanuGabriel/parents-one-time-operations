<?php

namespace EBS\ParentsOneTimeOperations\Commands;

use Throwable;
use EBS\ParentsOneTimeOperations\OneTimeOperationCreator;

class OneTimeOperationsMakeCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:make {name : The name of the one-time operation}';

    protected $description = 'Create a new one-time operation';

    public function handle(): int
    {
        try {
            $file = OneTimeOperationCreator::createOperationFile($this->argument('name'));
            $this->components->info(sprintf('One-time operation [%s] created successfully.', $file->getOperationName()));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->components->warn($e->getMessage());

            return self::FAILURE;
        }
    }
}
