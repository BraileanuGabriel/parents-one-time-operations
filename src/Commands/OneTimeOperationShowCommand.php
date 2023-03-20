<?php

namespace EBS\ParentsOneTimeOperations\Commands;

use Throwable;
use EBS\ParentsOneTimeOperations\Models\Operation;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationShowCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:show';

    protected $description = 'List of all one-time operations';

    public function handle(): int
    {
        try {

            $operationModels = Operation::all();
            $this->info("\n");

            if ($operationModels->isEmpty()) {
                $this->info('No operations found.');
            }

            $data = [];
            foreach ($operationModels as $operation) {
                $operationName = str_replace('_', '', ucwords(preg_replace('/\d+/u', '', $operation->name), '_'));
                $data[] = [
                    'name' => $this->green($operationName),
                    'created_at' => $this->white($operation->created_at)
                ];
            }

            $this->table(['name', 'created_at'], $data);

            $this->info("\n");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
