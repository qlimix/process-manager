<?php declare(strict_types=1);

namespace Qlimix\Process\Multiple;

use Qlimix\Process\Exception\ProcessException;

interface MultipleProcessRegistryInterface
{
    /**
     * @throws ProcessException
     */
    public function initialize(): void;

    /**
     * returns the index of the process that exited
     *
     * @throws ProcessException
     */
    public function removePid(int $stoppedPid): int;

    /**
     * @throws ProcessException
     */
    public function restartProcess(int $index): void;

    /**
     * @throws ProcessException
     */
    public function quit(): void;
}
