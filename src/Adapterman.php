<?php

namespace Adapterman;

use Exception;

class Adapterman
{
    public const VERSION = "0.4";

    public const NAME = "Adapterman v". self::VERSION;
    
    private const FUNCTIONS = ['header', 'header_remove', 'headers_sent', 'http_response_code', 'setcookie', 'session_create_id', 'session_id', 'session_name', 'session_save_path', 'session_status', 'session_start', 'session_write_close', 'set_time_limit'];

    public static function init(): void
    {
        try {
            self::checkVersion();
            self::checkFunctionsDisabled();
            
            // OK initialize the functions
            require __DIR__ . '/AdapterFunctions.php';

        } catch (Exception $e) {
            fwrite(STDOUT, self::NAME . " Error:\n\n");
            fwrite(STDOUT, $e->getMessage());
            exit;
        }
        
        fwrite(STDOUT, self::NAME . " OK\n\n");
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
            throw new Exception("* PHP version must be 8 or higher.\n* Actual PHP version: " . \PHP_VERSION . "\n\n ");
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
                throw new Exception("Functions not disabled in php.ini.\n" . self::showConfiguration());
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


