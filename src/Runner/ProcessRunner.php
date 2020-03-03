<?php declare(strict_types=1);

namespace Qlimix\Process\Runner;

use Qlimix\Process\Manager\ManagerInterface;
use Qlimix\Process\Runner\Exception\RunnerException;
use Qlimix\Process\Runtime\Reason;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Qlimix\Time\TimeLapseInterface;
use Throwable;

final class ProcessRunner implements RunnerInterface
{
    private ManagerInterface $manager;

    private RuntimeControlInterface $runtimeControl;

    private TimeLapseInterface $timeLapse;

    public function __construct(
        ManagerInterface $manager,
        RuntimeControlInterface $runtimeControl,
        TimeLapseInterface $timeLapse
    ) {
        $this->manager = $manager;
        $this->runtimeControl = $runtimeControl;
        $this->timeLapse = $timeLapse;
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        try {
            $this->manager->initialize();
        } catch (Throwable $exception) {
            throw new RunnerException('Failed to initialize process manager', 0, $exception);
        }

        while (true) {
            try {
                $this->manager->maintain();
            } catch (Throwable $exception) {
                $this->runtimeControl->quit(new Reason('Failed to maintain processes'));
            }

            if (!$this->manager->continue()) {
                $this->manager->stop();
                break;
            }

            $this->timeLapse->lapse();
        }
    }
}
