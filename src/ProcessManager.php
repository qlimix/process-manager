<?php declare(strict_types=1);

namespace Qlimix\ProcessManager;

use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Runtime\ControlInterface;

final class ProcessManager
{
    /** @var int[] */
    private $processes = [];

    /** @var int */
    private $maxProcesses = 5;

    /** @var ProcessInterface */
    private $process;

    /** @var ControlInterface */
    private $control;

    /** @var OutputInterface */
    private $output;

    /** @var bool */
    private $stop;

    /** @var bool */
    private $stoppedProcesses = false;

    /**
     * @param ProcessInterface $process
     * @param ControlInterface $control
     * @param OutputInterface $output
     */
    public function __construct(ProcessInterface $process, ControlInterface $control, OutputInterface $output)
    {
        $this->process = $process;
        $this->control = $control;
        $this->output = $output;
    }

    public function run(): void
    {
        while(true) {
            try {
                if ($this->quit()) {
                    $this->stopProcesses();
                }

                if (!$this->processLimit() && !$this->quit()) {
                    $this->fork();
                }

                $this->reap();
            } catch (\Throwable $exception) {
                $this->output->write($exception->getMessage());
                $this->stop = true;
            }

            $this->control->tick();

            if ($this->quit() && count($this->processes) === 0) {
                break;
            }

            sleep(1);
        }
    }

    /**
     * @throws \Exception
     */
    private function fork(): void
    {
        $this->output->write('Forking');
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \Exception('Could not start new process');
        }

        if ($pid > 0) {
            $this->processes[] = $pid;
            return;
        }

        try {
            $this->runProcess();
        } catch (\Throwable $exception) {
            exit(1);
        }

        exit(0);
    }

    /**
     * @throws \Exception
     */
    private function reap(): void
    {
        $status = -1;
        $this->output->write('Reaping');
        $pid = pcntl_wait($status, WNOHANG);

        if ($pid === 0) {
            $this->output->write('No child returned');
            return;
        }

        if ($pid === -1) {
            throw new \Exception('Failed waiting for a returning process');
        }

        if (pcntl_wifexited($status)) {
            $this->output->write('Found returned process');
            foreach ($this->processes as $index => $process) {
                if ($process === $pid) {
                    unset($this->processes[$index]);
                    return;
                }
            }
            throw new \Exception('Couldn\'t find pid in process list');
        }

        throw new \Exception('Process returned with '.pcntl_wexitstatus($status));
    }

    private function processLimit(): bool
    {
        return $this->maxProcesses === count($this->processes);
    }

    private function stopProcesses(): void
    {
        if ($this->stoppedProcesses) {
            return;
        }
        $this->output->write('Stop processes');
        foreach ($this->processes as $process) {
            posix_kill($process, SIGKILL);
        }

        $this->stoppedProcesses = true;
    }

    /**
     * @throws ProcessException
     */
    private function runProcess(): void
    {
        $this->output->write('Run process');
        $this->process->run($this->control, $this->output);
    }

    private function quit(): bool
    {
        return $this->stop || $this->control->abort();
    }
}
