<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;

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
     * @param ProcessControlInterface $processControl
     * @param RuntimeControlInterface $runtimeControl
     * @param OutputInterface $output
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
                    $this->restartProcess($pid);
                }
            } catch (\Throwable $exception) {
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
     * @param int $stoppedPid
     *
     * @throws ProcessException
     */
    private function restartProcess(int $stoppedPid): void
    {
        foreach ($this->pids as $pid => $index) {
            if ($pid === $stoppedPid) {
                if (!$this->quit()) {
                    $newPid = $this->processControl->startProcess($this->processes[$index]);
                    $this->pids[$newPid] = $index;
                }
                unset($this->pids[$stoppedPid]);
                return;
            }
        }

        throw new ProcessException('Invalid pid to restart');
    }

    private function quit(): bool
    {
        return $this->stop || $this->runtimeControl->abort();
    }
}
