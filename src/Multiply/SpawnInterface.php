<?php declare(strict_types=1);

namespace Qlimix\Process\Multiply;

use Qlimix\Process\Exception\ProcessException;

interface SpawnInterface
{
    /**
     * @throws ProcessException
     */
    public function spawn(): void;
}
