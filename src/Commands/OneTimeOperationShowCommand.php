<?php

namespace EBS\ParentsOneTimeOperations\Commands;

use Throwable;
use EBS\ParentsOneTimeOperations\Models\Operation;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationShowCommand extends OneTimeOperationsCommand
{
    protected $signature = 'operations:show {filter?* : List of filters: pending|processed|disposed}';

    protected $description = 'List of all one-time operations';

    protected $validFilters = [
        self::LABEL_PENDING,
        self::LABEL_PROCESSED,
        self::LABEL_DISPOSED,
    ];

    public function handle(): int
    {
        try {
            $this->validateFilters();

            $operationModels = Operation::all();
            $operationFiles = OneTimeOperationManager::getAllOperationFiles();
            $this->info("\n");

            foreach ($operationModels as $operation) {
                if (OneTimeOperationManager::fileExistsByName($operation->name)) {
                    continue;
                }

                $this->shouldDisplay(self::LABEL_DISPOSED) && $this->table([$this->default($operation->name), $this->white($operation->processed_at).' '.$this->green(self::LABEL_DISPOSED)], []);
            }

            foreach ($operationFiles->toArray() as $file) {
                if ($model = $file->getModel()) {
                    $this->shouldDisplay(self::LABEL_PROCESSED) &&  $this->table([$this->default($model->name), $this->white($model->processed_at).' '.$this->green(self::LABEL_PROCESSED)], []);
                } else {
                    $this->shouldDisplay(self::LABEL_PENDING) && $this->table([$this->default($file->getOperationName()), $this->default(self::LABEL_PENDING)], []);
                }
            }

            if ($operationModels->isEmpty() && $operationFiles->isEmpty()) {
                $this->info('No operations found.');
            }

            $this->info("\n");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @throws Throwable
     */
    protected function validateFilters(): void
    {
        $filters = array_map(function ($filter) {
            return strtolower($filter);
        }, $this->argument('filter'));
        $validFilters = array_map(function ($filter) {
            return strtolower($filter);
        }, $this->validFilters);

        if(array_diff($filters, $validFilters)){
            throw new \Exception('Given filter is not valid. Allowed filters: '.implode('|', array_map('strtolower', $this->validFilters)));
        }
    }

    protected function shouldDisplay(string $filterName): bool
    {
        $givenFilters = $this->argument('filter');

        if (empty($givenFilters)) {
            return true;
        }

        $givenFilters = array_map(function ($filter) {
            return strtolower($filter);
        }, $givenFilters);

        return in_array(strtolower($filterName), $givenFilters);
    }
}
