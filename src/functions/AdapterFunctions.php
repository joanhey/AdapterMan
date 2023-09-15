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

use Adapterman\Http;

/**
 * Send a raw HTTP header
 *
 * @link https://php.net/manual/en/function.header.php
 */
function header(string $header, bool $replace = true, int $response_code = 0): void
{
    Http::header($header, $replace, $response_code);
}

/**
 * Remove previously set headers
 *
 * @link https://php.net/manual/en/function.header-remove.php
 */
function header_remove(?string $name = null): void
{
    Http::headerRemove($name);  //TODO fix case-insensitive
}

/**
 * Get or Set the HTTP response code
 *
 * @link https://www.php.net/manual/en/function.http-response-code.php
 */
function http_response_code(int $code = null): int
{ // int|bool
    return Http::responseCode($code); // Fix to return actual status when void
}

/**
 * Returns a list of response headers sent (or ready to send)
 *
 * @return array<string>
 * 
 * @link https://www.php.net/manual/en/function.headers-list.php
 */
function headers_list(): array
{
    return Http::headers_list();
}

if (! function_exists('getallheaders')) { // It's declared in a dev lib
    /**
     * Fetch all HTTP request headers
     *
     * @return array<string,string>
     * @link https://www.php.net/manual/en/function.getallheaders.php
     */
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))))] = $value;
            }
        }

        return $headers;
    }
}

/**
 * Send a cookie
 *
 * @link https://php.net/manual/en/function.setcookie.php
 */
function setcookie(string $name, string $value = '', int|array $expires = 0, string $path = '', string $domain = '', bool $secure = false, bool $httponly = false): bool
{
    $samesite = '';
    if (is_array($expires)) { // Alternative signature available as of PHP 7.3.0 (not supported with named parameters)
        $expires  = $expires['lifetime'] ?? 0;
        $path     = $expires['path'] ?? '';
        $domain   = $expires['domain'] ?? '';
        $secure   = $expires['secure'] ?? false;
        $httponly = $expires['httponly'] ?? false;
        $samesite = $expires['samesite'] ?? '';
    }

    return Http::setCookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite);
}



/**
 * Limits the maximum execution time
 *
 * @link https://php.net/manual/en/function.set-time-limit.php
 */
function set_time_limit(int $seconds): bool
{
    // Disable set_time_limit to not stop the worker
    // by default CLI sapi use 0 (unlimited)
    return true;
}

/**
 * Checks if or where headers have been sent
 *
 * @link https://www.php.net/manual/en/function.headers-sent.php
 * 
 * @return bool Always false with Adapterman
 */
function headers_sent(string &$filename = null, int &$line = null): bool
{
    return false;
}

/**
 * Get cpu count
 *
 */
function cpu_count(): int
{
    // Windows does not support the number of processes setting.
    if (\DIRECTORY_SEPARATOR === '\\') {
        return 1;
    }
    $count = 4;
    if (\is_callable('shell_exec')) {
        if (\strtolower(PHP_OS) === 'darwin') {
            $count = (int)\shell_exec('sysctl -n machdep.cpu.core_count');
        } else {
            $count = (int)\shell_exec('nproc');
        }
    }
    return $count > 0 ? $count : 2;
}

/* function exit(string $status = ''): void {  //string|int
    Http::end($status);
} // exit and die are language constructors, change your code with an empty ExitException
 */
