<?php declare(strict_types=1);

namespace Qlimix\ProcessManager;

use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\ProcessManagerInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class ProcessManager implements ProcessManagerInterface
{
    /** @var int[] */
    private $runningProcesses = 0;

    /** @var int */
    private $maxProcesses = 5;

    /** @var ProcessInterface */
    private $process;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /** @var OutputInterface */
    private $output;

    /** @var bool */
    private $stop;

    /**
     * @param ProcessInterface $process
     * @param ProcessControlInterface $processControl
     * @param RuntimeControlInterface $runtimeControl
     * @param OutputInterface $output
     */
    public function __construct(
        ProcessInterface $process,
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl,
        OutputInterface $output
    ) {
        $this->process = $process;
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
        $this->output = $output;
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

                if ($this->runningProcesses > 0 && $this->processControl->status() !== null) {
                    $this->runningProcesses--;
                }
            } catch (\Throwable $exception) {
                $this->output->write($exception->getMessage());
                $this->stop = true;
            }

            $this->runtimeControl->tick();

            if ($this->runningProcesses === 0 && $this->quit()) {
                break;
            }

            sleep(1);
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
