<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 31.10.21 22:55:34
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\helper\Url;
use PHPUnit\Framework\TestCase;

/**
 * Class UrlTest
 */
class UrlTest extends TestCase
{
    /**
     * = => %3D
     * & => %26
     * [ => %5B
     * ] => %5D
     * тест => %D1%82%D0%B5%D1%81%D1%82
     */
    public const BUILD_QUERY_TESTS = [
        // 0
        [
            'in' => [],
            'out' => ''
        ],
        // 1
        [
            'in' => [''],
            'out' => '0'
        ],
        // 2
        [
            'in' => [
                '' => []
            ],
            'out' => ''
        ],
        // 3
        [
            'in' => ['', ''],
            'out' => '0&1'
        ],
        // 4
        [
            'in' => ['' => ''],
            'out' => ''
        ],
        // 5
        [
            'in' => ['' => 5],
            'out' => ''
        ],
        // 6
        [
            'in' => [23 => ''],
            'out' => '23'
        ],
        // 7
        [
            'in' => [
                'a' => []
            ],
            'out' => 'a[]'
        ],
        // 8
        [
            'in' => [
                'a' => [
                    '' => 5
                ]
            ],
            'out' => 'a[]'
        ],
        // 9
        [
            'in' => [1],
            'out' => '0=1'
        ],
        // 10
        [
            'in' => ['a' => null],
            'out' => '',
        ],
        // 11
        [
            'in' => ['a' => ''],
            'out' => 'a'
        ],
        // 12
        [
            'in' => [
                'a' => [1, 2, 3]
            ],

            'out' => 'a[]=1&a[]=2&a[]=3'
        ],
        // 13
        [
            'in' => [
                'a' => [
                    25 => 14
                ]
            ],
            'out' => 'a[25]=14'
        ],
        // 14
        [
            'in' => [
                'a' => 'тест'
            ],
            'out' => 'a=%D1%82%D0%B5%D1%81%D1%82'
        ],
        // 15
        [
            'in' => [
                'a' => '=',
                'b' => '&',
                'c' => '[]'
            ],
            'out' => 'a=%3D&b=%26&c=%5B%5D'
        ],
        // 16
        [
            'in' => ['тест[]' => ''],
            'out' => '%D1%82%D0%B5%D1%81%D1%82%5B%5D'
        ],
        // 17
        [
            'in' => [
                '' => 1,
                1 => 'a',
                'a' => null,
                'b' => '',
                'c' => 'тест',
                'd' => '&',
                'e' => [],
                'f' => [
                    '' => '',
                    'a' => [
                        2, 3
                    ],
                    'b' => '',
                    'c' => '=',
                    'd' => ['a' => 4]
                ],
                'g' => ['' => ''],
                'тест' => ''
            ],

            'out' => '1=a&b&c=%D1%82%D0%B5%D1%81%D1%82&d=%26&e[]&f[a][]=2&f[a][]=3&f[b]&f[c]=%3D&f[d][a]=4&g[]&%D1%82%D0%B5%D1%81%D1%82'
        ]
    ];

    /**
     * Test.
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testBuildQuery() : void
    {
        foreach (self::BUILD_QUERY_TESTS as $i => $test) {
            self::assertSame(
                $test['out'],
                Url::buildQuery($test['in']),
                'Ошибка теста запросов №: ' . $i
            );
        }
    }

    /**
     * Test.
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testDiffQuery() : void
    {
        $tests = [
            1 => [
                'a' => '',
                'b' => ['a' => 1],
                'c' => []
            ],
            2 => [
                'a' => 'a=1',
                'b' => '',
                'c' => ['a' => '1']
            ],
            3 => [
                'a' => 'a=Master',
                'b' => 'a=master',
                'c' => []
            ],
            4 => [
                'a' => ['a' => ['Val1', 'Val2']],
                'b' => ['a' => ['val2'], 'b' => 3],
                'c' => ['a' => ['Val1']]
            ]
        ];

        foreach ($tests as $n => $test) {
            self::assertSame(
                $test['c'],
                Url::diffQuery($test['a'], $test['b'], ['noCase' => true]),
                'Test №' . $n
            );
        }
    }
}
