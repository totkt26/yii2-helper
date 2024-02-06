<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.01.22 19:03:41
 */

declare(strict_types = 1);
namespace dicr\helper;

use function count;
use function is_array;

/**
 * Работа с массивами.
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * Удаляет элемент из массива.
     * В отличие от оригинала поддерживает комплексный ключ.
     *
     * @param array $array исходный массив
     * @param string|array $key ключ для удаления. Может быть строкой с путем ключа, разделенным "." или массивом.
     * @param mixed $default
     * @return mixed
     */
    public static function remove(&$array, $key, $default = null): mixed
    {
        if (! is_array($key)) {
            $key = explode('.', $key);
        }

        while (count($key) > 1) {
            $index = array_shift($key);
            if (! isset($array[$index]) || ! is_array($array[$index])) {
                return $default;
            }

            $array = &$array[$index];
        }

        $index = array_shift($key);
        $ret = $array[$index] ?? $default;
        unset($array[$index]);

        return $ret;
    }
}
