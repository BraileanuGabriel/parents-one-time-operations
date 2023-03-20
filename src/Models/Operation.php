<?php

namespace  EBS\ParentsOneTimeOperations\Models;

use Illuminate\Database\Eloquent\Model;
use EBS\ParentsOneTimeOperations\Models\Factories\OperationFactory;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class Operation extends Model
{
    public $timestamps = false;

    public const DISPATCHED_ASYNC = 'async';

    public const DISPATCHED_SYNC = 'sync';

    protected $fillable = [
        'name',
        'dispatched',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = OneTimeOperationManager::getTableName();
    }

    public static function storeOperation(string $operation, bool $async): self
    {
        return self::firstOrCreate([
            'name' => $operation,
            'dispatched' => $async ? self::DISPATCHED_ASYNC : self::DISPATCHED_SYNC,
            'processed_at' => now(),
        ]);
    }

    public function getFilePathAttribute(): string
    {
        return OneTimeOperationManager::pathToFileByName($this->name);
    }
}
