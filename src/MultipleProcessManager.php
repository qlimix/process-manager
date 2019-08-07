<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultipleProcessManager implements ProcessManagerInterface
{
    /** @var ProcessInterface[] */
    private $processes;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /**
     * @param ProcessInterface[] $processes
     */
    public function __construct(
        array $processes,
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl
    ) {
        $this->processes = $processes;
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        foreach ($this->processes as $process) {
            $this->processControl->startProcess($process);
        }
    }

    /**
     * @inheritDoc
     */
    public function maintain(): void
    {
        $exit = $this->processControl->status();
        if ($exit === null) {
            return;
        }

        if ($exit->isSuccess()) {
            $this->processControl->startProcess($exit->getProcess());
        } else {
            $this->runtimeControl->quit();
        }
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function stop(): void
    {
        try {
            $this->processControl->stopProcesses();
        } catch (Throwable $exception) {
        }
    }

    /**
     * @inheritDoc
     */
    public function continue(): bool
    {
        $this->runtimeControl->tick();

        return $this->runtimeControl->abort();
    }
}
