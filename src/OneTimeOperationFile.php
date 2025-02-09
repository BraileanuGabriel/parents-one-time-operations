<?php

namespace EBS\ParentsOneTimeOperations;

use Illuminate\Support\Facades\File;
use SplFileInfo;
use EBS\ParentsOneTimeOperations\Models\Operation;

class OneTimeOperationFile
{
    protected $file;

    protected $classObject = null;

    public static function make(SplFileInfo $file): self
    {
        return new self($file);
    }

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    public function getOperationName(): string
    {
        $pathElements = explode(DIRECTORY_SEPARATOR, $this->file->getRealPath());
        $filename = end($pathElements);

        return substr($filename, 0, strpos($filename, ".php"));;
    }

    public function getClassObject(): OneTimeOperation
    {
        if (! $this->classObject) {
            $this->classObject = File::getRequire($this->file);
        }

        return $this->classObject;
    }

    public function getModel(): ?Operation
    {
        return Operation::whereName($this->getOperationName())->first();
    }
}
