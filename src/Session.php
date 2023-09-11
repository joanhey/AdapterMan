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
     * Session Cookie params
     */
    public static array $sessionCookie = [
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => false,
            'samesite' => 'Lax',
    ];

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

        static::$sessionCookie['lifetime'] = (int) \ini_get('session.cookie_lifetime');
        static::$sessionCookie['path'] = (string) \ini_get('session.cookie_path');
        static::$sessionCookie['domain'] = (string) \ini_get('session.cookie_domain');
        static::$sessionCookie['secure'] = (bool) \ini_get('session.cookie_secure');
        static::$sessionCookie['httponly'] = (bool) \ini_get('session.cookie_httponly');
        if (static::checkCookieSamesite(\ini_get('session.cookie_samesite'))) {
            static::$sessionCookie['samesite'] = \ini_get('session.cookie_samesite');
        }
            

        Timer::add(static::$sessionGcInterval, function () {
            static::tryGcSessions();
        });
    }

    protected static function createSessionCookie(string $name, string $id): bool
    {
        return static::setcookie(
            $name,
            $id,
            static::$sessionCookie['lifetime'],
            static::$sessionCookie['path'],
            static::$sessionCookie['domain'],
            static::$sessionCookie['secure'],
            static::$sessionCookie['httponly'],
            static::$sessionCookie['samesite']
        );
    }

    /**
     * Returns the current session status
     *
     * @see https://www.php.net/manual/en/function.session-status.php
     */
    public static function sessionStatus(): int
    {
        if (static::sessionStarted()) {
            if (static::$sessionFile) {
                return \PHP_SESSION_ACTIVE;
            }

            return \PHP_SESSION_NONE;
        }             


        return \PHP_SESSION_DISABLED;
    }

    /**
     * Session create id.
     * @see https://www.php.net/manual/en/function.session-create-id.php
     */
    public static function sessionCreateId(string $prefix = ''): string|false
    {
        // if ($prefix === '') {

        // }
        return \bin2hex(\pack('d', \hrtime(true)).\random_bytes(8));
    }

    /**
     * Get and/or set the current session id.
     *
     * @see https://www.php.net/manual/en/function.session-id.php
     */
    public static function sessionId(?string $id = null): string|false
    {
        if ($id === null) {
            if (static::sessionStarted() && static::$sessionFile) {
                return \str_replace('ses_', '', \basename(static::$sessionFile));
            }
            return '';
        }
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

            return static::createSessionCookie(static::$sessionName, $session_id);
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

        return static::createSessionCookie(static::$sessionName, $session_id);
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

    /**
     * Set the session cookie parameters
     * 
     * @see https://www.php.net/manual/en/function.session-set-cookie-params.php
     *
     * @param integer $lifetime_or_options
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httponly
     * 
     * @return boolean Returns **true** on success or **false** on failure.
     */
    public static function sessionSetCookieParams(
        int|array $lifetime_or_options,
        ?string   $path = null,
        ?string   $domain = null,
        ?bool     $secure = null,
        ?bool     $httponly = null
    ): bool
    {
        if (static::sessionStarted()) {
            return false;
        }

        if (\is_array($lifetime_or_options)) {
            //Validate keys
            if (\array_diff_key($lifetime_or_options, static::$sessionCookie) === []) {
                $options = \array_filter($lifetime_or_options, fn ($value) => !\is_null($value));
                
                if(isset($options['samesesite']) && $options['samesesite'] && !static::checkSession($options['samesesite'])) {
                    return false;
                }
                
                static::$sessionCookie = $options + static::$sessionCookie;

                return true;
            }

            return false;
        }

        static::$sessionCookie['lifetime'] = $lifetime_or_options;
        $params = [
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly,
        ];

        foreach ($params as $key => $value) {
            if (! \is_null($value)) {
                static::$sessionCookie[$key] = $value;
            }
        }

        return true;
    }

    /**
     * Get the session cookie parameters
     * 
     * @see https://www.php.net/manual/en/function.session-get-cookie-params.php
     */
    public static function sessionGetCookieParams(): array
    {
        return static::$sessionCookie;
    }
}

}
