<?php declare(strict_types=1);

namespace Qlimix\ProcessManager;

use Qlimix\Process\Exception\ProcessException;
use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;

final class ProcessControl implements ProcessControlInterface
{
    /** @var int[] */
    private $processes = [];

    /** @var RuntimeControlInterface */
    private $control;

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $nextPid = 0;

    /**
     * @param RuntimeControlInterface $control
     * @param OutputInterface $output
     */
    public function __construct(RuntimeControlInterface $control, OutputInterface $output)
    {
        $this->control = $control;
        $this->output = $output;
    }

    public function status(): ?int
    {
        $status = -1;
        $this->output->write('Reaping');
        $pid = pcntl_wait($status, WNOHANG);

        if ($pid === 0) {
            $this->output->write('No child returned');
            return null;
        }

        if ($pid === -1) {
            throw new ProcessException('Failed waiting for a returning process');
        }

        if (pcntl_wifexited($status)) {
            $this->output->write('Found returned process');
            foreach ($this->processes as $index => $process) {
                if ($process === $pid) {
                    unset($this->processes[$index]);
                    return $index;
                }
            }
            throw new ProcessException('Couldn\'t find pid in process list');
        }

        throw new ProcessException('Process returned with '.pcntl_wexitstatus($status));
    }

    /**
     * @inheritDoc
     */
    public function isProcessRunning(int $pid): bool
    {
        if (!isset($this->processes[$pid])) {
            return false;
        }

        $status = -1;
        $this->output->write('Reaping');
        $pid = pcntl_waitpid($pid, $status, WNOHANG);

        if ($pid === 0) {
            $this->output->write('No child returned');
            return true;
        }

        if ($pid === -1) {
            throw new ProcessException('Failed waiting for a returning process');
        }

        if (pcntl_wifexited($status)) {
            $this->output->write('Found returned process');
            foreach ($this->processes as $index => $process) {
                if ($process === $pid) {
                    unset($this->processes[$index]);
                    return false;
                }
            }
            throw new ProcessException('Couldn\'t find pid in process list');
        }

        throw new ProcessException('Process returned with '.pcntl_wexitstatus($status));
    }

    public function startProcess(ProcessInterface $process): int
    {
        $this->output->write('Forking');
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new ProcessException('Could not start new process');
        }

        if ($pid > 0) {
            $this->processes[$this->nextPid++] = $pid;
            return $pid;
        }

        try {
            $process->run($this->control, $this->output);
        } catch (\Throwable $exception) {
            exit(1);
        }

        exit(0);
    }

    /**
     * @inheritDoc
     */
    public function startProcesses(array $processes): int
    {
        foreach ($processes as $process) {
            $this->startProcess($process);
        }
    }

    public function stopProcess(int $pid): void
    {
        if (!isset($this->processes[$pid])) {
            return;
        }

        posix_kill($pid, SIGKILL);
    }

    public function stopProcesses(): void
    {
        $this->output->write('Stop processes');
        foreach ($this->processes as $process) {
            posix_kill($process, SIGKILL);
        }
    }
}
