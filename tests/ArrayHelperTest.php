<?php
/*
 * @copyright 2019-2020 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 13.10.20 14:15:43
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\helper\ArrayHelper;
use PHPUnit\Framework\TestCase;

/**
 * ArrayHelper test.
 */
class ArrayHelperTest extends TestCase
{
    /**
     * Тест remove
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testRemove() : void
    {
        $arr = [
            'a' => 1,
            'b' => [
                'c' => 2,
                'd' => 3,
                'e' => 4
            ]
        ];

        $res = ArrayHelper::remove($arr, ['b', 'd']);
        self::assertSame(3, $res);
        self::assertSame([
            'a' => 1,
            'b' => [
                'c' => 2,
                'e' => 4
            ]
        ], $arr);

        $res = ArrayHelper::remove($arr, 'b.e');
        self::assertSame(4, $res);
        self::assertSame([
            'a' => 1,
            'b' => [
                'c' => 2
            ]
        ], $arr);

        $res = ArrayHelper::remove($arr, 'b');
        self::assertSame(['c' => 2], $res);
        self::assertSame(['a' => 1], $arr);

        $res = ArrayHelper::remove($arr, 'b', 123);
        self::assertSame(123, $res);
        self::assertSame(['a' => 1], $arr);
    }
}
