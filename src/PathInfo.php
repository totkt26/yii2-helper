<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.01.22 18:56:42
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;
use yii\base\BaseObject;

use function array_slice;
use function count;

/**
 * Модель файлового пути.
 *
 * @property-read string $path весь путь
 * @property-read bool $isAbsolute признак абсолютного
 * @property-read string|null $absolute абсолютный путь
 * @property-read string $parent родительский путь
 * @property-read string $file имя файла с расширением без пути
 * @property-read string $name имя без расширения
 * @property-read string $ext расширение файла
 */
class PathInfo extends BaseObject
{
    private string $_path;

    private string $_parent;

    private string $_file;

    /** @var string имя файла без расширения */
    private string $_name;

    /** @var string расширение */
    private string $_ext;

    /**
     * Конструктор
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->_path = self::normalize($path);

        parent::__construct();
    }

    /**
     * Нормализует путь. Вырезает лишние / и ..
     *
     * @param string $path
     * @return string
     */
    public static function normalize(string $path) : string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        $parts = [];
        $isAbsolute = str_starts_with($path, DIRECTORY_SEPARATOR);

        foreach (preg_split('~/~um', $path, - 1, PREG_SPLIT_NO_EMPTY) as $part) {
            $partsEnd = end($parts);

            if ($part === '..') {
                if (empty($parts)) {
                    if (! $isAbsolute) {
                        $parts[] = $part;
                    }
                } elseif ($partsEnd === '..' || $partsEnd === '.') {
                    $parts[] = $part;
                } else {
                    array_pop($parts);
                }
            } elseif ($part === '.') {
                if (! $isAbsolute && empty($parts)) {
                    $parts[] = $part;
                }
            } else {
                $parts[] = $part;
            }
        }

        return ($isAbsolute ? '/' : '') . implode('/', $parts);
    }

    /**
     * Проверяет является ли путь абсолютным
     *
     * @param string $path
     * @return bool
     */
    public static function isAbsolute(string $path) : bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        return str_starts_with($path, '/');
    }

    /**
     * Возвращает абсолютный путь.
     *
     * @param string $path относительный путь
     * @return string|null абсолютный путь или null если не существует
     */
    public static function absolute(string $path) : ?string
    {
        $realPath = realpath($path);
        return $realPath === false ? null : $realPath;
    }

    /**
     * Возвращает родительский путь
     *
     * @param string $path
     * @param int $levels
     * @return string
     */
    public static function parent(string $path, int $levels = 1) : string
    {
        if ($levels < 0) {
            throw new InvalidArgumentException('levels');
        }

        if (empty($levels)) {
            return $path;
        }

        $isAbsolute = static::isAbsolute($path);

        /** @var string[] $parts */
        $parts = preg_split('~/~um', static::normalize($path), - 1, PREG_SPLIT_NO_EMPTY);
        $partsEnd = end($parts);

        if (empty($parts)) {
            if (! $isAbsolute) {
                $parts = array_fill(0, $levels, '..');
            }
        } elseif ($partsEnd === '..' || $partsEnd === '.') {
            $parts = array_merge($parts, array_fill(count($parts), $levels, '..'));
        } elseif ($parts < $levels) {
            $parts = $isAbsolute ? [] : array_fill(0, $levels - count($parts), '..');
        } else {
            $parts = array_slice($parts, 0, count($parts) - $levels);
        }

        return ($isAbsolute ? '/' : '') . implode('/', $parts);
    }

    /**
     * Возвращает дочерний путь
     *
     * @param string $path
     * @param string $relative
     * @return string
     */
    public static function child(string $path, string $relative) : string
    {
        return static::normalize($path . '/' . $relative);
    }

    /**
     * Возвращает имя файла (basename).
     *
     * @param string $path
     * @return string
     */
    public static function file(string $path) : string
    {
        return (string)pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Возвращает имя файла без расширения
     *
     * @param string $path
     * @return string
     */
    public static function name(string $path) : string
    {
        return (string)pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Возвращает расширение файла.
     *
     * @param string $path
     * @return string
     */
    public static function ext(string $path) : string
    {
        return (string)pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Возвращает путь
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->_path;
    }

    /**
     * Возвращает признак абсолютного пути
     *
     * @return bool
     */
    public function getIsAbsolute() : bool
    {
        return static::isAbsolute($this->_path);
    }

    /**
     * Возвращает абсолютный путь
     *
     * @return string|null абсолютный путь или null если не существует
     */
    public function getAbsolute() : ?string
    {
        return static::absolute($this->_path);
    }

    /**
     * Возвращает родительский путь
     *
     * @param int $levels
     * @return string
     */
    public function getParent(int $levels = 1) : string
    {
        if (! isset($this->_parent)) {
            $this->_parent = static::parent($this->_path, $levels);
        }

        return $this->_parent;
    }

    /**
     * Возвращает дочерний путь
     *
     * @param string $relative
     * @return string
     */
    public function getChild(string $relative) : string
    {
        return static::child($this->_path, $relative);
    }

    /**
     * Возвращает файл (basename)
     *
     * @return string
     */
    public function getFile() : string
    {
        if (! isset($this->_file)) {
            $this->_file = static::file($this->_path);
        }

        return $this->_file;
    }

    /**
     * Возвращает имя файла без расширения
     *
     * @return string
     */
    public function getName() : string
    {
        if (! isset($this->_name)) {
            $this->_name = static::name($this->_path);
        }

        return $this->_name;
    }

    /**
     * Возвращает расширение
     *
     * @return string
     */
    public function getExt() : string
    {
        if (! isset($this->_ext)) {
            $this->_ext = static::ext($this->_path);
        }

        return $this->_ext;
    }

    /**
     * Конвертирование в строку
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->_path;
    }
}
