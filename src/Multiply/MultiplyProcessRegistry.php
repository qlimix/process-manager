<?php declare(strict_types=1);

namespace Qlimix\Process\Multiply;

use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;

final class MultiplyProcessRegistry implements MultiplyProcessRegistryInterface
{
    /** @var ProcessInterface */
    private $process;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var int */
    private $maxProcesses;

    /** @var int */
    private $runningProcesses = 0;

    public function __construct(
        ProcessInterface $process,
        ProcessControlInterface $processControl,
        int $maxProcesses
    ) {
        $this->process = $process;
        $this->processControl = $processControl;
        $this->maxProcesses = $maxProcesses;
    }

    /**
     * @inheritDoc
     */
    public function spawn(): void
    {
        if ($this->maxProcesses === $this->runningProcesses) {
            return;
        }

        $this->processControl->startProcess($this->process);
        $this->runningProcesses++;
    }

    /**
     * @inheritDoc
     */
    public function despawned(): void
    {
        $this->runningProcesses--;
    }

    /**
     * @inheritDoc
     */
    public function quit(): void
    {
        $this->processControl->stopProcesses();

        do {
            $status = $this->processControl->status();
            if ($status !== null) {
                continue;
            }

            $this->despawned();
        } while ($this->runningProcesses > 0);
    }
}
