<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultipleProcessesManager implements ProcessManagerInterface
{
    /** @var ProcessInterface[] */
    private $processes;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /** @var OutputInterface */
    private $output;

    /** @var int[] */
    private $pids;

    /** @var bool */
    private $stop;

    /**
     * @param ProcessInterface[] $processes
     */
    public function __construct(
        array $processes,
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl,
        OutputInterface $output
    ) {
        $this->processes = $processes;
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
        $this->output = $output;
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->pids = $this->processControl->startProcesses($this->processes);

        while(true) {
            try {
                if ($this->quit()) {
                    $this->processControl->stopProcesses();
                }

                $pid = $this->processControl->status();
                if ($pid !== null) {
                    $index = $this->removePid($pid->getPid());
                    if ($pid->success()) {
                        $this->restartProcess($index);
                    } else {
                        $this->stop = true;
                    }
                }
            } catch (Throwable $exception) {
                $this->output->write($exception->getMessage());
                $this->stop = true;
            }

            $this->runtimeControl->tick();

            if (count($this->pids) === 0 && $this->quit()) {
                break;
            }

            usleep(50000);
        }
    }

    /**
     * returns the index of the process that exited
     *
     * @throws ProcessException
     */
    private function removePid(int $stoppedPid): int
    {
        foreach ($this->pids as $pid => $index) {
            if ($pid === $stoppedPid) {
                unset($this->pids[$stoppedPid]);
                return $index;
            }
        }

        throw new ProcessException('Invalid pid to restart');
    }

    /**
     * @throws ProcessException
     */
    private function restartProcess(int $index): void
    {
        if (!array_key_exists($index, $this->pids[])) {
            throw new ProcessException('Invalid process index');
        }

        $newPid = $this->processControl->startProcess($this->processes[$index]);
        $this->pids[$newPid] = $index;
    }

    private function quit(): bool
    {
        return $this->stop || $this->runtimeControl->abort();
    }
}
