<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Multiply\SpawnInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultiplyProcessManager implements ProcessManagerInterface
{
    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    /** @var SpawnInterface */
    private $spawn;

    public function __construct(
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl,
        SpawnInterface $spawn
    ) {
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
        $this->spawn = $spawn;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->spawn->spawn();
    }

    /**
     * @inheritDoc
     */
    public function maintain(): void
    {
        $exitedProcess = $this->processControl->status();
        if ($exitedProcess === null) {
            return;
        }

        if ($exitedProcess->isSuccess()) {
            $this->spawn->spawn();
        } else {
            $this->runtimeControl->quit();
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        try {
            $this->processControl->stopProcesses();
        } catch (Throwable $exception) {
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
