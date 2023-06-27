<?php

namespace Tests;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunAdapterman
{
    private static Process $adapterman;

    public static function start(): void
    {
        if(isset(self::$adapterman)) {
            return;
        }
        self::$adapterman = new Process(['php', '-c', __DIR__ . '/../cli-php.ini',  __DIR__ . '/AppServer.php',  'start']);
        self::$adapterman->setTimeout(null);
        self::$adapterman->start();
        sleep(1);

        echo self::$adapterman->getOutput();
    }

    public static function stop(): void
    {
        self::$adapterman->stop();
    }

}
