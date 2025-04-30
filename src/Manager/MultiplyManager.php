<?php declare(strict_types=1);

namespace Qlimix\Process\Manager;

use Qlimix\Process\Control\ControlInterface;
use Qlimix\Process\Manager\Exception\ManagerException;
use Qlimix\Process\Manager\Multiply\SpawnInterface;
use Qlimix\Process\Runtime\Reason;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultiplyManager implements ManagerInterface
{
    public function __construct(
        private ControlInterface $control,
        private RuntimeControlInterface $runtimeControl,
        private SpawnInterface $spawn
    ) {
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        try {
            $this->spawn->spawn();
        } catch (Throwable $exception) {
            throw new ManagerException('Failed to initialize', 0, $exception);
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

        if (!$exit->success) {
            $this->runtimeControl->quit(new Reason('Process '.$exit->process.' failed'));
        }

        try {
            $this->spawn->spawn();
        } catch (Throwable $exception) {
            throw new ManagerException('Failed to spawn,'.$exit->process.' , process', 0, $exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        try {
            $this->control->stopAll();
        } catch (Throwable) {
        }
    }

    /**
     * @inheritDoc
     */
    public function continue(): bool
    {
        $this->runtimeControl->tick();

        return $this->runtimeControl->abort();
    }
}
