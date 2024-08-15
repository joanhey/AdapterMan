<?php
/**
 * This file is part of Adapterman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    Joan Miquel<https://github.com/joanhey>
 * @copyright Joan Miquel<https://github.com/joanhey>
 * @link      https://github.com/joanhey/AdapterMan
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Adapterman;

use Workerman\Worker;
use Exception;
use Workerman\Timer;
use Workerman\Worker;

class Adapterman
{
    public const VERSION = "0.6.1";

    public const NAME = 'Adapterman/'. self::VERSION. ' (Workerman/'. Worker::VERSION. ')';

    private const FUNCTIONS = [
        'header',
        'header_remove',
        'headers_sent',
        'headers_list',
        'http_response_code',

        'setcookie',

        'session_create_id',
        'session_id',
        'session_name',
        'session_save_path',
        'session_status',
        'session_start',
        'session_write_close',
        'session_regenerate_id',
        'session_unset',
        'session_get_cookie_params',
        'session_set_cookie_params',

        'set_time_limit',
    ];

    public static function init(): void
    {
        try {
            self::checkVersion();
            self::checkFunctionsDisabled();

            // OK initialize the functions
            require __DIR__ . '/functions/AdapterFunctions.php';
            require __DIR__ . '/functions/AdapterSessionFunctions.php';
            class_alias(Http::class, \Protocols\Http::class);
            Http::init();

        } catch (Exception $e) {
            fwrite(STDERR, self::NAME . ' Error:' . PHP_EOL);
            fwrite(STDERR, $e->getMessage());
            exit;
        }
    }

    public static function initWorker(
        string $socketName = '',
        array $socketOptions = [],
        string $processName = 'AdapterMan',
        int $workersCount = 1,
        int $sessionTTL = 600,
        callable $onMessage = null
    ): Worker
    {
        try {
            self::init();

            $worker = new Worker($socketName, $socketOptions);
            if ($processName) {
                $worker->name = $processName;
            }

            if ($workersCount === 0) {
                $worker->count = cpu_count();
            } else {
                $worker->count = $workersCount;
            }

            $worker->onWorkerStart = static function (Worker $worker) use ($sessionTTL) {
                if ($worker->id === 0) {
                    Timer::add($sessionTTL, static function () {
                        Http::tryGcSessions();
                    });
                }
            };

            if ($onMessage) {
                $worker->onMessage = $onMessage;
            }

            return $worker;

        } catch (Exception $e) {
            fwrite(STDERR, self::NAME . ' Error:' . PHP_EOL);
            fwrite(STDERR, $e->getMessage());
            exit;
        }
    }

    /**
     * Check PHP version
     *
     * @throws Exception
     * @return void
     */
    private static function checkVersion(): void
    {
        if (\PHP_MAJOR_VERSION < 8) {
            throw new Exception("* PHP version must be 8 or higher." . PHP_EOL . "* Actual PHP version: " . \PHP_VERSION . PHP_EOL);
        }
    }

    /**
     * Check that functions are disabled in php.ini
     *
     * @throws Exception
     * @return void
     */
    private static function checkFunctionsDisabled(): void
    {

        foreach (self::FUNCTIONS as $function) {
            if (\function_exists($function)) {
                throw new Exception("Functions not disabled in php.ini." . PHP_EOL . self::showConfiguration());
            }
        }
    }

    private static function showConfiguration(): string
    {
        $inipath = \php_ini_loaded_file();
        $methods = \implode(',', self::FUNCTIONS);

        return "Add in file: $inipath" . PHP_EOL . "disable_functions=$methods" . PHP_EOL;
    }
}


