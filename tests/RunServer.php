<?php

namespace Tests;

use Symfony\Component\Process\Process;

class RunServer
{
    private static Process $server;

    public static function start(): void
    {
        if (isset(self::$server)) {
            return;
        }

        self::$server = new Process(['php', '-c', __DIR__ . '/../cli-php.ini',  __DIR__ . '/AppServer.php',  'start']);
        self::$server->setTimeout(null);
        self::$server->start();
        sleep(1);

        echo self::$server->getOutput();
    }

    public static function stop(): void
    {
        if (!isset(self::$server)) {
            return;
        }
        
        self::$server->stop();
    }
}
