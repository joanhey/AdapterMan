<?php

use Workerman\Protocols\Http;

/**
 * Send a raw HTTP header
 * 
 * @link https://php.net/manual/en/function.header.php
 */
function header(string $content, bool $replace = true, int $http_response_code = null): void
{
    Http::header($content, $replace, $http_response_code);
}

/**
 * Remove previously set headers
 *
 * @param string $name  The header name to be removed. This parameter is case-insensitive.
 * @return void
 */
function header_remove(string $name): void
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
 * Send a cookie
 *
 * @param string $name
 * @param string $value
 * @param integer $expires
 * @param string $path
 * @param string $domain
 * @param boolean $secure
 * @param boolean $httponly
 * @return boolean
 * 
 * @link https://php.net/manual/en/function.setcookie.php
 */
function setcookie(string $name, string $value = '', int $expires = 0, string $path = '', string $domain = '', bool $secure = FALSE, bool $httponly = FALSE): bool
{
    return Http::setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
}

function session_create_id(string $prefix = ''): string
{
    return Http::sessionCreateId();  //TODO fix to use $prefix
}

function session_id(string $id = ''): string
{
    return Http::sessionId($id);   //TODO fix return session name or '' if not exists session
}

function session_name(string $name = ''): string
{
    return Http::sessionName($name);
}

function session_save_path(string $path = ''): string
{
    return Http::sessionSavePath($path);
}

function session_status(): int
{
    if (Http::sessionStarted() === false) {
        return 1;
    }
    return 2;
}

function session_start(array $options = []): bool
{
    return Http::sessionStart();   //TODO fix $options
}

function session_write_close(): void
{
    Http::sessionWriteClose();
}

function set_time_limit(int $seconds): bool
{
    // Disable set_time_limit to not stop the worker
    // by default CLI sapi use 0 (unlimited)
    return true;
}

/* function exit(string $status = ''): void {  //string|int
    Http::end($status);
} // exit and die are language constructors, change your code with an empty ExitException
 */
