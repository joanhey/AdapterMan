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
function header(string $content, bool $replace = true, int $http_response_code = 0): void
{
    Http::header($content, $replace, $http_response_code);
}

/**
 * Remove previously set headers
 *
 * @param string $name  The header name to be removed. This parameter is case-insensitive.
 * @return void
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
 * @param integer $code [optional] The optional response_code will set the response code.
 * @return integer      The current response code. By default the return value is int(200).
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
 * @param string $name
 * @param string $value
 * @param int|array $expires
 * @param string $path
 * @param string $domain
 * @param boolean $secure
 * @param boolean $httponly
 * @return boolean
 *
 * @link https://php.net/manual/en/function.setcookie.php
 */
function setcookie(string $name, string $value = '', int|array $expires = 0, string $path = '', string $domain = '', bool $secure = FALSE, bool $httponly = FALSE): bool
{
    if (is_array($expires)) { // Alternative signature available as of PHP 7.3.0 (not supported with named parameters)
        $expires  = $expires['expires'] ?? 0;
        $path     = $expires['path'] ?? '';
        $domain   = $expires['domain'] ?? '';
        $secure   = $expires['secure'] ?? FALSE;
        $httponly = $expires['httponly'] ?? FALSE;
    }

    return Http::setCookie($name, $value, $expires, $path, $domain, $secure, $httponly);
}

/**
 * Create new session id
 *
 * @param string $prefix
 * @return string
 */
function session_create_id(string $prefix = ''): string
{
    return Http::sessionCreateId();  //TODO fix to use $prefix
}

/**
 * Get and/or set the current session id
 *
 * @param string $id
 * @return string
 * 
 * @link https://www.php.net/manual/en/function.session-id.php
 */
function session_id(string $id = ''): string
{
    return Http::sessionId($id);   //TODO fix return session name or '' if not exists session
}

/**
 * Get and/or set the current session name
 *
 * @link https://www.php.net/manual/en/function.session-name.php
 */
function session_name(?string $name = null): string|false
{
    return Http::sessionName($name);
}

/**
 * Get and/or set the current session save path
 *
 * @param string $path
 * @return string
 */
function session_save_path(?string $path = null): string|false
{
    return Http::sessionSavePath($path);
}

/**
 * Returns the current session status
 *
 */
function session_status(): int
{
    return Http::sessionStatus();
}

/**
 * Start new or resume existing session
 *
 * @param array $options
 * @return bool
 */
function session_start(array $options = []): bool
{
    return Http::sessionStart();   //TODO fix $options
}

/**
 * Write session data and end session
 *
 * @return bool
 * 
 * @link https://www.php.net/manual/en/function.session-write-close.php
 */
function session_write_close(): bool
{
    return Http::sessionWriteClose();
}

/**
 * Update the current session id with a newly generated one
 *
 * @param bool $delete_old_session
 * @return bool
 *
 * @link https://www.php.net/manual/en/function.session-regenerate-id.php
 */
function session_regenerate_id(bool $delete_old_session = false): bool
{
    return Http::sessionRegenerateId($delete_old_session);
}

/**
 * Limits the maximum execution time
 *
 * @param int $seconds
 * @return bool
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
 * @return int
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

/** add CONSTANTS not enabled in cli SAPI */

/** SESSION */
const PHP_SESSION_DISABLED = 0;
const PHP_SESSION_NONE = 1;
const PHP_SESSION_ACTIVE = 2;

//const SID // Not possible, use session_status();

