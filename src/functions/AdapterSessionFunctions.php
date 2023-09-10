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
 * Session functions
 */

 /**
 * Create new session id
 *
 * @param string $prefix
 * @return string|false
 */
function session_create_id(string $prefix = ""): string|false
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
function session_id(?string $id = null): string|false
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

