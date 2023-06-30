<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\RunServer;

abstract class ServerTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RunServer::start();
    }

    public static function tearDownAfterClass(): void
    {
        //RunAdapterman::stop();
    }
    public function __destruct() {
        RunServer::stop();
    }
}
