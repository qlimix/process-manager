<?php declare(strict_types=1);

namespace Qlimix\Process\Manager;

use Qlimix\Process\Control\ControlInterface;
use Qlimix\Process\Manager\Exception\ManagerException;
use Qlimix\Process\Runtime\Reason;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultipleManager implements ManagerInterface
{
    /** @var string[] */
    private array $processes;

    private ControlInterface $control;

    private RuntimeControlInterface $runtimeControl;

    /**
     * @param string[] $processes
     */
    public function __construct(
        array $processes,
        ControlInterface $control,
        RuntimeControlInterface $runtimeControl
    ) {
        $this->processes = $processes;
        $this->control = $control;
        $this->runtimeControl = $runtimeControl;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        try {
            $this->control->startMultiple($this->processes);
        } catch (Throwable $exception) {
            throw new ManagerException('Failed to start all processes', 0, $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function maintain(): void
    {
        try {
            $exit = $this->control->status();
        } catch (Throwable $exception) {
            throw new ManagerException('Failed to wait for a status of a process', 0, $exception);
        }

        if ($exit === null) {
            return;
        }

        if (!$exit->isSuccess()) {
            $this->runtimeControl->quit(new Reason('Process '.$exit->getProcess().' failed'));
        }

        try {
            $this->control->start($exit->getProcess());
        } catch (Throwable $exception) {
            throw new ManagerException('Failed to restart,'.$exit->getProcess().' , process', 0, $exception);
        }
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function stop(): void
    {
        try {
            $this->control->stopAll();
        } catch (Throwable $exception) {
        }
    }

    /**
     * @inheritDoc
     */
    public function continue(): bool
    {
        $this->runtimeControl->tick();

        return !$this->runtimeControl->abort();
    }
}
