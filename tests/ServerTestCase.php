<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\RunAdapterman;

abstract class ServerTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RunAdapterman::start();
    }

    public static function tearDownAfterClass(): void
    {
        //RunAdapterman::stop();
    }
    public function __destruct() {
        RunAdapterman::stop();
    }
}
