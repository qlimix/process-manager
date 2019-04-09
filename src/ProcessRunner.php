<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Exception\ProcessRunnerException;
use Qlimix\Process\Output\OutputInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Qlimix\Time\TimeLapseInterface;
use Throwable;

final class ProcessRunner implements ProcessRunnerInterface
{
    /** @var ProcessManagerInterface */
    private $processManager;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /** @var TimeLapseInterface */
    private $timeLapse;

    /** @var OutputInterface */
    private $output;

    public function __construct(
        ProcessManagerInterface $processManager,
        RuntimeControlInterface $runtimeControl,
        TimeLapseInterface $timeLapse,
        OutputInterface $output
    ) {
        $this->processManager = $processManager;
        $this->runtimeControl = $runtimeControl;
        $this->timeLapse = $timeLapse;
        $this->output = $output;
    }

    /**
     * @throws ProcessRunnerException
     */
    public function run(): void
    {
        try {
            $this->processManager->initialize();
        } catch (Throwable $exception) {
            throw new ProcessRunnerException('Failed to initialize process manager', 0, $exception);
        }

        while (true) {
            try {
                $this->processManager->maintain();
            } catch (Throwable $exception) {
                $this->output->write((string) $exception);
                $this->runtimeControl->quit();
            }

            if (!$this->processManager->continue()) {
                $this->processManager->stop();
                break;
            }

            $this->timeLapse->lapse(50000);
        }
    }
}
