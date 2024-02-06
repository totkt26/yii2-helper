<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 19.12.20 18:05:28
 */

/** @noinspection ForgottenDebugOutputInspection */
declare(strict_types = 1);
namespace dicr\helper;

use function debug_backtrace;
use function var_dump;

use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const YII_DEBUG;

/**
 * Класс для отладки.
 */
class Debug
{
    /**
     * Дамп значения в html.
     *
     * @param mixed ...$vars
     */
    public static function xmp(...$vars) : void
    {
        if (YII_DEBUG) {
            echo '<xmp>';

            foreach ($vars as $var) {
                var_dump($var);
            }

            echo '</xmp>';
        }
    }

    /**
     * Дамп значений и выход.
     *
     * @param mixed ...$vars
     */
    public static function xe(...$vars) : void
    {
        if (YII_DEBUG) {
            echo '<xmp>';

            foreach ($vars as $var) {
                var_dump($var);
            }

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            echo '░' . $trace[0]['file'] . ':' . $trace[0]['line'] . "░\n";

            exit;
        }
    }
}
