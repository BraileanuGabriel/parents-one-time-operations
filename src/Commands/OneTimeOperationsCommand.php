<?php

namespace EBS\ParentsOneTimeOperations\Commands;

use Illuminate\Console\Command;
use EBS\ParentsOneTimeOperations\OneTimeOperationManager;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

abstract class OneTimeOperationsCommand extends Command
{
    protected const SUCCESS = 'SUCCESS';

    protected const FAILURE = 'FAILURE';

    protected const LABEL_PROCESSED = 'PROCESSED';

    protected const LABEL_PENDING = 'PENDING';

    protected const LABEL_DISPOSED = 'DISPOSED';

    protected $operationsDirectory;

    public function __construct()
    {
        parent::__construct();
        $this->operationsDirectory = OneTimeOperationManager::getDirectoryPath();
    }

    protected function green(string $message): string
    {
        return sprintf('<fg=green>%s</>', $message);
    }

    protected function white(string $message): string
    {
        return sprintf('<fg=white>%s</>', $message);
    }

    protected function default(string $message): string
    {
        return sprintf('<fg=default>%s</>', $message);
    }

    /**
     * @throws Throwable
     */
    protected function task($description, $task = null, $verbosity = OutputInterface::VERBOSITY_NORMAL){

        $descriptionWidth = mb_strlen(preg_replace("/\<[\w=#\/\;,:.&,%?]+\>|\\e\[\d+m/", '$1', $description) ?? '');

        $this->output->write("  $description ", false, $verbosity);

        $startTime = microtime(true);

        $result = false;

        try {
            $result = ($task ?: function () {
                return true;
            })();
        } finally {
            $runTime = $task
                ? (' '.number_format((microtime(true) - $startTime) * 1000).'ms')
                : '';

            $runTimeWidth = mb_strlen($runTime);
            $width = 150;
            $dots = max($width - $descriptionWidth - $runTimeWidth - 10, 0);

            $this->output->write(str_repeat($this->white('.'), $dots), false, $verbosity);
            $this->output->write($this->white($runTime), false, $verbosity);

            $this->output->writeln(
                $result !== false ? ' <fg=green;options=bold>DONE</>' : ' <fg=red;options=bold>FAIL</>',
                $verbosity
            );
        }
    }
}
