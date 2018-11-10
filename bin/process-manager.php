#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

final class TestProcess implements \Qlimix\Process\ProcessInterface
{
    public function run(\Qlimix\Process\Runtime\RuntimeControlInterface $control, \Qlimix\Process\Output\OutputInterface $output): void
    {
        $i = 0;
        $output->write('PID: '.getmypid());
        while (true) {
            if ($i > 5) {
                break;
            }
            $control->tick();

            if ($control->abort()) {
                $output->write('PID: '.getmypid().' exiting');
                break;
            }

            sleep(mt_rand(1,3));
            $i++;
        }
    }
}

$control = new \Qlimix\Process\Runtime\PcntlRuntimeControl();
$control->init();

$processControl = new \Qlimix\ProcessManager\ProcessControl(
    $control,
    new \Qlimix\Process\Output\StdOutput()
);

$processManager = new \Qlimix\ProcessManager\ProcessManager(
    new TestProcess(),
    $processControl,
    $control,
    new \Qlimix\Process\Output\StdOutput()
);

$processManager->run();
