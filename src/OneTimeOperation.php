<?php

namespace EBS\ParentsOneTimeOperations;

abstract class OneTimeOperation
{
    /**
     * Determine if the operation is being processed asyncronously.
     */
    protected $async = true;

    /**
     * Process the operation.
     */
    abstract public function process(): void;

    public function isAsync(): bool
    {
        return $this->async;
    }
}
