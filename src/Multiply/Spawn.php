<?php declare(strict_types=1);

namespace Qlimix\Process\Multiply;

use Qlimix\Process\ProcessControlInterface;
use Qlimix\Process\ProcessInterface;
use Qlimix\Process\Registry\RegistryInterface;

final class Spawn implements SpawnInterface
{
    /** @var ProcessInterface */
    private $process;

    /** @var ProcessControlInterface */
    private $processControl;

    /** @var RegistryInterface */
    private $registry;

    /** @var int */
    private $maxProcesses;

    public function __construct(
        ProcessInterface $process,
        ProcessControlInterface $processControl,
        RegistryInterface $registry,
        int $maxProcesses
    ) {
        $this->process = $process;
        $this->processControl = $processControl;
        $this->registry = $registry;
        $this->maxProcesses = $maxProcesses;
    }

    /**
     * @inheritDoc
     */
    public function spawn(): void
    {
        if ($this->maxProcesses === $this->registry->count()) {
            return;
        }

        $this->processControl->startProcess($this->process);
    }
}
