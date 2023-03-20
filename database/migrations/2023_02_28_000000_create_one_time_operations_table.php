<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use EBS\ParentsOneTimeOperations\Models\Operation;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;

class CreateOneTimeOperationsTable extends Migration
{
    protected $name;

    public function __construct()
    {
        $this->name = OneTimeOperationManager::getTableName();
    }

    public function up()
    {
        Schema::create($this->name, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('dispatched', [Operation::DISPATCHED_SYNC, Operation::DISPATCHED_ASYNC]);
            $table->timestamp('processed_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->name);
    }
}
