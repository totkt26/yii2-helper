<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 31.10.21 22:55:34
 */

declare(strict_types = 1);
namespace dicr\tests;

use dicr\helper\PathInfo;
use PHPUnit\Framework\TestCase;

/**
 * PathInfo Test.
 */
class PathInfoTest extends TestCase
{
    /**
     *
     */
    public const TEST_NORMALIZE = [
        '' => '',

        '/' => '/',
        '//./' => '/',
        '/../..' => '/',
        '/../../' => '/',
        '/../../../path' => '/path',

        '.' => '.',
        './/' => '.',
        '../././/../' => '../..',
        './../' => './..',
        './../path/../..' => './../..',
        '..' => '..',
        '..//.' => '..',
        '../../' => '../..',
        '....' => '....',

        'path' => 'path',
        'path/' => 'path',
        'path/to/test' => 'path/to/test',
        'path/./' => 'path',
        '/path/./../' => '/',
        '/path' => '/path',
        './path' => './path',
        '../path/' => '../path',
        './../path' => './../path',
        '././path' => './path',
        '../../path' => '../../path',
        '/../../path' => '/path'
    ];

    /**
     * Test UrlInfo::normalizePath
     *
     * @noinspection PhpMethodMayBeStaticInspection
     * @noinspection PhpUnitMissingTargetForTestInspection
     */
    public function testNormalizePath() : void
    {
        foreach (self::TEST_NORMALIZE as $path => $res) {
            self::assertSame($res, PathInfo::normalize($path), 'path: ' . $path);
        }
    }

    /**
     *
     */
    public const TEST_PARENT = [
        '' => '..',
        '/' => '/',
        '/path' => '/',
        '/./path' => '/',
        '/../path' => '/',
        '.' => './..',
        './../' => './../..',
        '..' => '../..',
        '.././' => '../..',
        '././path' => '.',
        '../path' => '..',
        'path' => '',
        'path/../' => '..',
        'path/.' => '',
        'path/../..' => '../..'
    ];

    /**
     * Test.
     *
     * @noinspection PhpMethodMayBeStaticInspection
     */
    public function testParent() : void
    {
        foreach (self::TEST_PARENT as $path => $res) {
            self::assertSame($res, PathInfo::parent($path), 'parent: ' . $path);
        }
    }
}
