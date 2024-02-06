<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.01.22 19:03:41
 */

declare(strict_types = 1);
namespace dicr\helper;

use Yii;
use yii\base\BaseObject;
use yii\log\Logger;

use function basename;
use function debug_backtrace;

/**
 * Лог.
 *
 * В Yii::debug отключены логи если не установлен YII_DEBUG
 */
class Log extends BaseObject
{
    /**
     * Отладочное сообщение.
     *
     * @param mixed $msg
     * @param ?string $category
     */
    public static function debug(mixed $msg, ?string $category = null): void
    {
        if ($category === null) {
            $category = self::guessCateg();
        }

        // workaround, так как сообщения уровня в Yii::debug выводятся только при YII_DEBUG
        Yii::$app->log->getLogger()->log($msg, Logger::LEVEL_TRACE, $category);
    }

    /**
     * Информация.
     *
     * @param $msg
     * @param ?string $category
     */
    public static function info($msg, ?string $category = null): void
    {
        if ($category === null) {
            $category = self::guessCateg();
        }

        Yii::$app->log->getLogger()->log($msg, Logger::LEVEL_INFO, $category);
    }

    /**
     * Предупреждение.
     *
     * @param mixed $msg
     * @param ?string $category
     */
    public static function warn(mixed $msg, ?string $category = null): void
    {
        if ($category === null) {
            $category = self::guessCateg();
        }

        Yii::$app->log->getLogger()->log($msg, Logger::LEVEL_WARNING, $category);
    }

    /**
     * Ошибка.
     *
     * @param mixed $msg
     * @param ?string $category
     */
    public static function error(mixed $msg, ?string $category = null): void
    {
        if ($category === null) {
            $category = self::guessCateg();
        }

        Yii::$app->log->getLogger()->log($msg, Logger::LEVEL_ERROR, $category);
    }

    /**
     * Определяет категорию сообщения из стека трассировки.
     *
     * @return ?string
     */
    private static function guessCateg(): ?string
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $frame = array_pop($traces);

        if (isset($frame['class'])) {
            $categ = $frame['class'] . '::' . $frame['function'];
        } elseif (isset($frame['function'])) {
            $categ = $frame['function'];
        } elseif (isset($frame['file'])) {
            $categ = basename($frame['file']);
        } else {
            $categ = null;
        }

        return $categ;
    }
}
