<?php

namespace Lazysess;

abstract class Manager
{
    private static $class;

    public static function start()
    {
        $_SESSION = self::getClass();
    }

    private static function is_started()
    {
        return self::$class !== null;
    }

    /**
     * @static
     * @param string $id
     * @return string
     * @see http://php.net/manual/en/function.session-id.php
     */
    public static function id()
    {
        session_start();
        $result = func_num_args() > 0 ? session_id(func_get_arg(0)) : session_id();

        if (self::is_started()) {
            session_write_close();
            self::start();
        }

        return $result;
    }

    public static function close()
    {
        if (self::$class !== null) {
            self::getClass()->close();
            self::$class = null;
            self::gc();
        }
    }

    public static function destroy()
    {
        session_start();
        $result = session_destroy();
        return $result;
    }

    /**
     * @static
     * @return Session
     */
    private static function getClass()
    {
        if (!self::$class) {
            self::$class = new Session();
        }
        return self::$class;
    }

    /**
     * @static
     * Garbage collector
     * Remove duplicated sessions Set-Cookie headers
     */
    private static function gc()
    {
        $headers = headers_list();
        $session_cookie = false;

        for ($i = sizeof($headers) - 1; $i >= 0; $i--) {
            if (strpos($headers[$i], 'Set-Cookie: ' . session_name() . '=') === 0) {
                if ($session_cookie) {
                    unset($headers[$i]);
                } else {
                    $session_cookie = true;
                }
            }
        }

        if ($session_cookie) {
            header_remove();

            foreach ($headers as $header) {
                header($header, false);
            }
        }
    }

}
