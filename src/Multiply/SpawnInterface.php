<?php declare(strict_types=1);

namespace Qlimix\Process\Multiply;

use Qlimix\Process\Multiply\Exception\SpawnException;

interface SpawnInterface
{
    /**
     * @throws SpawnException
     */
    public function spawn(): void;
}
