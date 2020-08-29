<?php

namespace Adapterman;

use Exception;  // Añadir tests, posiblemenente con behat

class Adapterman
{
    public const VERSION = "0.2";
    
    private const FUNCTIONS = ['header', 'header_remove', 'http_response_code', 'setcookie', 'session_create_id', 'session_id', 'session_name', 'session_save_path', 'session_status', 'session_start', 'session_write_close', 'set_time_limit'];

    public static function init(): void
    {
        self::checkVersion();
        self::checkFunctionsDisabled();

        // OK initialize the functions
        require __DIR__ . '/AdapterFunctions.php';
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
            throw new Exception("\n\n* PHP version must be 8 or higher.\n* Actual PHP version: " . \PHP_VERSION . "\n\n ");
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
                throw new Exception("\n\nFunctions not disabled in php.ini.\n" . self::showConfiguration());
            }
        }
    }

    private static function showConfiguration(): string
    {
        $inipath = \php_ini_loaded_file();
        $methods = \implode(',', self::FUNCTIONS);

        return "Add in file: $inipath \n\ndisable_functions=$methods \n\n ";
    }
}


