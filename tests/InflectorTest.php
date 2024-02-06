<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 22.03.21 13:09:49
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\helper\Inflector;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class InflectorTest
 */
class InflectorTest extends TestCase
{
    /**
     * Тестовые переменные.
     *
     * @return array
     */
    private static function vars(): array
    {
        $obj = new stdClass();
        $obj->field = 12345;

        return [
            'color' => 'red ',
            'prod' => [
                'name' => '<Чайник>',
                'price' => ''
            ],
            'obj' => $obj
        ];
    }

    /**
     * varValue test
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testVarValue(): void
    {
        $vars = static::vars();

        $tests = [
            'color' => 'red ',
            'color|upper' => 'RED ',
            'color|lower|trim' => 'red',
            'color|lower|unknown' => null,
            'prod' => null,
            'prod|name|esc' => '&lt;Чайник&gt;',
            'prod|price' => '',
            'prod|unknown' => null,
            'obj' => null,
            'obj|field' => '12345',
            'obj|field|asInteger' => '12,345',
            'obj|field|unknown|trim' => null,
            'unknown' => null,
            'unknown|number' => null,
        ];

        foreach ($tests as $path => $val) {
            self::assertSame($val, Inflector::varValue($vars, $path), $path);
        }
    }

    /**
     * test replaceBlockVars
     */
    public static function testReplaceBlockVars(): void
    {
        $vars = static::vars();

        $tests = [
            [
                'text' => 'правильное значение ${color|upper} переменной',
                'opts' => [],
                'result' => 'правильное значение RED переменной'
            ],
            [
                'text' => 'несуществующее значение ${} переменной',
                'opts' => [],
                'result' => 'несуществующее значение ${} переменной',
            ],
            [
                'text' => 'очистка ${null} переменной',
                'opts' => ['cleanVars' => true],
                'result' => 'очистка переменной',
            ],
            [
                'text' => 'очистка ${null} текста',
                'opts' => ['cleanText' => true],
                'result' => '',
            ]
        ];

        foreach ($tests as $test) {
            self::assertSame(
                $test['result'],
                Inflector::replaceBlockVars($test['text'], $vars, $test['opts']),
                $test['text']
            );
        }
    }

    /**
     * test replaceVars
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testReplaceVars(): void
    {
        $vars = self::vars();

        $tests = [
            [
                'text' => 'Текст без блоков и переменных',
                'opts' => [],
                'result' => 'Текст без блоков и переменных'
            ],
            [
                'text' => 'Текст без блоков с ${color} переменной',
                'opts' => [],
                'result' => 'Текст без блоков с red переменной'
            ],
            [
                'text' => 'Текст без блоков с неизвестной очищаемой ${unknown} переменной',
                'opts' => ['cleanVars' => true],
                'result' => 'Текст без блоков с неизвестной очищаемой переменной'
            ],
            [
                'text' => 'Текст без блоков очищаемый с неизвестной ${unknown} переменной',
                'opts' => ['cleanText' => true],
                'result' => ''
            ],
            [
                'text' => 'Текст [[с ${color} блоком]] переменной',
                'opts' => [],
                'result' => 'Текст с red блоком переменной'
            ],
            [
                'text' => 'Текст [[с ${} неизвестной]] переменной',
                'opts' => [],
                'result' => 'Текст с ${} неизвестной переменной'
            ],
            [
                'text' => 'Текст [[с очищаемой ${} неизвестной]] переменной',
                'opts' => ['cleanVars' => true],
                'result' => 'Текст с очищаемой неизвестной переменной'
            ],
            [
                'text' => 'Текст [[с очищаемым ${} блоком]] переменной',
                'opts' => ['cleanText' => true],
                'result' => 'Текст переменной'
            ],
            [
                'text' => 'Текст с переменной в конце ${color}',
                'opts' => [],
                'result' => 'Текст с переменной в конце red '
            ],
            [
                'text' => '[[Блок текста с переменной в конце ${color}]]',
                'opts' => [],
                'result' => 'Блок текста с переменной в конце red '
            ],
            [
                'text' => 'Несколько пробелов ${color}  ${} в середине',
                'opts' => [],
                'result' => 'Несколько пробелов red ${} в середине'
            ]
        ];

        foreach ($tests as $test) {
            self::assertSame(
                $test['result'],
                Inflector::replaceVars($test['text'], $vars, $test['opts']),
                $test['text']
            );
        }
    }
}
