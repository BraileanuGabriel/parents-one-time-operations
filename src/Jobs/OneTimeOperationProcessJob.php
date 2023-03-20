<?php

namespace EBS\ParentsOneTimeOperations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class OneTimeOperationProcessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $operationName;

    public function __construct(string $operationName)
    {
        $this->operationName = $operationName;
    }

    public function handle(): void
    {
        OneTimeOperationManager::getClassObjectByName($this->operationName)->process();
    }
}
