<?php

namespace EBS\ParentsOneTimeOperations;

abstract class OneTimeOperation
{
    /**
     * Process the operation.
     */
    abstract public function process(): void;
}
