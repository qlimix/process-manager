<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Multiply\MultiplyProcessRegistryInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultiplyProcessManager implements ProcessManagerInterface
{
    /** @var MultiplyProcessRegistryInterface */
    private $multiplyProcessManager;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    public function __construct(
        MultiplyProcessRegistryInterface $multiplyProcessManager,
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl
    ) {
        $this->multiplyProcessManager = $multiplyProcessManager;
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->multiplyProcessManager->spawn();
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

        $this->multiplyProcessManager->despawned();

        if (!$exitedProcess->success()) {
            $this->runtimeControl->quit();
        }

        $this->multiplyProcessManager->spawn();
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        try {
            $this->multiplyProcessManager->quit();
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
