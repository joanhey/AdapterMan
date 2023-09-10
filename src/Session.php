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

use Workerman\Timer;

trait Session
{
    /**
     * Session save path.
     */
    public static string $sessionSavePath = '';

    /**
     * Session name.
     */
    public static string $sessionName = '';

    /**
     * Session gc max lifetime.
     */
    public static int $sessionGcMaxLifeTime = 1440;

    /**
     * Session cookie lifetime.
     */
    public static int $sessionCookieLifetime;

    /**
     * Session cookie path.
     */
    public static string $sessionCookiePath;

    /**
     * Session cookie domain.
     */
    public static string $sessionCookieDomain;

    /**
     * Session cookie secure.
     */
    public static bool $sessionCookieSecure;

    /**
     * Session cookie httponly.
     */
    public static bool $sessionCookieHttponly;

    /**
     * Session gc interval.
     */
    public static int $sessionGcInterval = 600;

    /**
     * Session started.
     */
    protected static bool $sessionStarted = false;

    /**
     * Session file.
     */
    protected static string $sessionFile = '';

    /**
     * Init.
     *
     * @return void
     */
    public static function sessionInit()
    {
        if (! static::$sessionName) {
            static::$sessionName = \ini_get('session.name');
        }

        if (! static::$sessionSavePath) {
            $savePath = \ini_get('session.save_path');
            if (\preg_match('/^\d+;(.*)$/', $savePath, $match)) {
                $savePath = $match[1];
            }
            if (! $savePath || \str_starts_with($savePath, 'tcp://')) {
                $savePath = \sys_get_temp_dir();
            }
            static::$sessionSavePath = $savePath;
        }

        if ($gc_max_life_time = \ini_get('session.gc_maxlifetime')) {
            static::$sessionGcMaxLifeTime = (int) $gc_max_life_time;
        }

        static::$sessionCookieLifetime = (int) \ini_get('session.cookie_lifetime');
        static::$sessionCookiePath = (string) \ini_get('session.cookie_path');
        static::$sessionCookieDomain = (string) \ini_get('session.cookie_domain');
        static::$sessionCookieSecure = (bool) \ini_get('session.cookie_secure');
        static::$sessionCookieHttponly = (bool) \ini_get('session.cookie_httponly');

        Timer::add(static::$sessionGcInterval, function () {
            static::tryGcSessions();
        });
    }

    /**
     * Session create id.
     */
    public static function sessionCreateId(): string
    {
        return \bin2hex(\pack('d', \hrtime(true)).\random_bytes(8));
    }

    /**
     * Get and/or set the current session id.
     *
     * @return string
     */
    public static function sessionId(string $id = null): string
    {
        if (static::sessionStarted() && static::$sessionFile) {
            return \str_replace('ses_', '', \basename(static::$sessionFile));
        }

        return '';
    }

    /**
     * Get and/or set the current session name.
     * @see https://www.php.net/manual/en/function.session-name.php
     */
    public static function sessionName(?string $name = null): string|false
    {
        if ($name === null) {
            return static::$sessionName;
        }

        if (! static::sessionStarted() && ! ctype_digit($name) && ctype_alnum($name)) {
            $session_name = static::$sessionName;
            static::$sessionName = $name;
            return $session_name;
        } 

        return false;
    }

    /**
     * Get and/or set the current session save path.
     * 
     * @see https://www.php.net/manual/en/function.session-save-path.php
     */
    public static function sessionSavePath(?string $path = null): string|false
    {
        if ($path === null) {
            return static::$sessionSavePath;
        }

        if (! static::sessionStarted() && \is_dir($path) && \is_writable($path)) {
            return static::$sessionSavePath = $path;
        }

        return false;
    }

    /**
     * Session started.
     */
    public static function sessionStarted(): bool
    {
        return static::$sessionStarted;
    }

    /**
     * Session start.
     */
    public static function sessionStart(): bool
    {
        if (static::$sessionStarted) {
            return true;
        }
        static::$sessionStarted = true;
        // Generate a SID.
        if (! isset($_COOKIE[static::$sessionName]) || ! \is_file(static::$sessionSavePath.'/ses_'.$_COOKIE[static::$sessionName])) {
            // Create a unique session_id and the associated file name.
            while (true) {
                $session_id = static::sessionCreateId();
                if (! \is_file($file_name = static::$sessionSavePath.'/ses_'.$session_id)) {
                    break;
                }
            }
            static::$sessionFile = $file_name;

            return static::setcookie(
                static::$sessionName, $session_id, static::$sessionCookieLifetime, static::$sessionCookiePath, static::$sessionCookieDomain, static::$sessionCookieSecure, static::$sessionCookieHttponly
            );
        }
        if (! static::$sessionFile) {
            static::$sessionFile = static::$sessionSavePath.'/ses_'.$_COOKIE[static::$sessionName];
        }
        // Read session from session file.
        $raw = \file_get_contents(static::$sessionFile);
        if ($raw) {
            $_SESSION = \unserialize($raw);
        }

        return true;
    }

    /**
     * Save session.
     */
    public static function sessionWriteClose(): bool
    {
        if (static::$sessionStarted) {
            $session_str = \serialize($_SESSION);
            if ($session_str && static::$sessionFile) {
                return (bool) \file_put_contents(static::$sessionFile, $session_str);
            }
        }

        return empty($_SESSION);
    }

    /**
     * Update the current session id with a newly generated one.
     *
     * @link https://www.php.net/manual/en/function.session-regenerate-id.php
     */
    public static function sessionRegenerateId(bool $delete_old_session = false): bool
    {
        $old_session_file = static::$sessionFile;
        // Create a unique session_id and the associated file name.
        while (true) {
            $session_id = static::sessionCreateId();
            if (! \is_file($file_name = static::$sessionSavePath.'/ses_'.$session_id)) {
                break;
            }
        }
        static::$sessionFile = $file_name;

        if ($delete_old_session) {
            \unlink($old_session_file);
        }

        return static::setcookie(
            static::$sessionName, $session_id, static::$sessionCookieLifetime, static::$sessionCookiePath, static::$sessionCookieDomain, static::$sessionCookieSecure, static::$sessionCookieHttponly
        );
    }

    /**
     * Try GC sessions.
     *
     * @return void
     */
    public static function tryGcSessions()
    {
        $time_now = \time();
        foreach (\glob(static::$sessionSavePath.'/ses*') as $file) {
            if (\is_file($file) && $time_now - \filemtime($file) > static::$sessionGcMaxLifeTime) {
                \unlink($file);
            }
        }
    }
}
