<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultiplyProcessManager implements ProcessManagerInterface
{
    /** @var ProcessInterface */
    private $process;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $maxProcesses;

    /** @var int[] */
    private $runningProcesses = 0;

    /** @var bool */
    private $stop;

    public function __construct(
        ProcessInterface $process,
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl,
        OutputInterface $output,
        int $maxProcesses
    ) {
        $this->process = $process;
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
        $this->output = $output;
        $this->maxProcesses = $maxProcesses;
    }

    public function run(): void
    {
        while(true) {
            try {
                if ($this->quit()) {
                    $this->processControl->stopProcesses();
                }

                if (!$this->processLimit() && !$this->quit()) {
                    $this->processControl->startProcess($this->process);
                    $this->runningProcesses++;
                }

                $exitedProcess = $this->processControl->status();
                if ($this->runningProcesses > 0 && $exitedProcess !== null) {
                    $this->runningProcesses--;
                    if (!$exitedProcess->success()) {
                        $this->stop = true;
                    }
                }
            } catch (Throwable $exception) {
                $this->output->write($exception->getMessage());
                $this->stop = true;
            }

            $this->runtimeControl->tick();

            if ($this->runningProcesses === 0 && $this->quit()) {
                break;
            }

            usleep(50000);
        }
    }

    private function processLimit(): bool
    {
        return $this->maxProcesses === $this->runningProcesses;
    }

    private function quit(): bool
    {
        return $this->stop || $this->runtimeControl->abort();
    }
}
