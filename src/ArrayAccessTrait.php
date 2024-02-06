<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.01.22 19:03:47
 */

declare(strict_types = 1);
namespace dicr\helper;

use ArrayAccess;

/**
 * Реализация интерфейса ArrayAccess.
 */
trait ArrayAccessTrait
{
    /**
     * @param int|string $offset
     * @return bool
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists(int|string $offset): bool
    {
        return isset($this->{$offset});
    }

    /**
     * @param int|string $offset
     * @return mixed
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet(int|string $offset): mixed
    {
        return $this->{$offset};
    }

    /**
     * @param int|string $offset
     * @param mixed $item
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet(int|string $offset, mixed $item): void
    {
        $this->{$offset} = $item;
    }

    /**
     * @param int|string $offset
     * @see \ArrayAccess::offsetUnset()
     */
    public function offsetUnset(int|string $offset): void
    {
        $this->{$offset} = null;
    }
}
