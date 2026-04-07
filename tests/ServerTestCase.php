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
}
