<?php declare(strict_types=1);

namespace Qlimix\Process;

use Qlimix\Process\Multiple\MultipleProcessRegistryInterface;
use Qlimix\Process\Runtime\RuntimeControlInterface;
use Throwable;

final class MultipleProcessManager implements ProcessManagerInterface
{
    /** @var MultipleProcessRegistryInterface */
    private $multipleProcessManager;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RuntimeControlInterface */
    private $runtimeControl;

    public function __construct(
        MultipleProcessRegistryInterface $multipleProcessManager,
        ProcessControlInterface $processControl,
        RuntimeControlInterface $runtimeControl
    ) {
        $this->multipleProcessManager = $multipleProcessManager;
        $this->processControl = $processControl;
        $this->runtimeControl = $runtimeControl;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->multipleProcessManager->initialize();
    }

    /**
     * @inheritDoc
     */
    public function maintain(): void
    {
        $pid = $this->processControl->status();
        if ($pid === null) {
            return;
        }

        $index = $this->multipleProcessManager->removePid($pid->getPid());
        if ($pid->success()) {
            $this->multipleProcessManager->restartProcess($index);
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
            $this->multipleProcessManager->quit();
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
