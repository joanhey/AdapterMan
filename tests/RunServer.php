<?php

namespace Tests;

use Symfony\Component\Process\Process;

class RunServer
{
    private static ?Process $server = null;

    public static function start(): void
    {
        if (self::$server !== null) {
            return;
        }

        self::$server = new Process(['php', '-c', __DIR__ . '/../cli-php.ini', __DIR__ . '/AdaptermanServer.php', 'start']);
        self::$server->setTimeout(null);
        self::$server->start();
        register_shutdown_function(function (): void {
            RunServer::stop();
        });
        sleep(1);

        echo self::$server->getOutput();
    }

    public static function stop(): void
    {
        if (self::$server === null) {
            return;
        }

        self::$server->stop();
        self::$server = null;
    }
}
