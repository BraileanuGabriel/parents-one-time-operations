<?php

namespace  EBS\ParentsOneTimeOperations\Models;

use Illuminate\Database\Eloquent\Model;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class Operation extends Model
{
    protected $fillable = [
        'name',
        'dispatched',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = OneTimeOperationManager::getTableName();
    }

    public static function storeOperation(string $operation): self
    {
        return self::firstOrCreate(['name' => $operation,]);
    }
}
