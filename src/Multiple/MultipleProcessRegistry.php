<?php declare(strict_types=1);

namespace Qlimix\Process\Multiple;

use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use function array_key_exists;
use function count;

final class MultipleProcessRegistry implements MultipleProcessRegistryInterface
{
    /** @var ProcessInterface[] */
    private $processes;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /** @var int[] */
    private $pids = [];

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
            $this->pids[] = $this->processControl->startProcess($process);
        }
    }

    /**
     * @inheritDoc
     */
    public function removePid(int $stoppedPid): int
    {
        foreach ($this->pids as $pid => $index) {
            if ($pid === $stoppedPid) {
                unset($this->pids[$stoppedPid]);
                return $index;
            }
        }

        throw new ProcessException('Invalid pid to remove');
    }

    /**
     * @inheritDoc
     */
    public function restartProcess(int $index): void
    {
        if ($this->runtimeControl->abort()) {
            return;
        }

        if (!array_key_exists($index, $this->processes)) {
            throw new ProcessException('Invalid process index');
        }

        $newPid = $this->processControl->startProcess($this->processes[$index]);
        $this->pids[$newPid] = $index;
    }

    /**
     * @inheritDoc
     */
    public function quit(): void
    {
        $this->processControl->stopProcesses();

        do {
            $status = $this->processControl->status();
            if ($status === null) {
                continue;
            }

            $this->removePid($status->getPid());
        } while (count($this->pids) > 0);
    }
}
