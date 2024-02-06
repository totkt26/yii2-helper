<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.01.22 19:03:47
 */

declare(strict_types = 1);
namespace dicr\helper;

use InvalidArgumentException;
use Yii;
use yii\base\ExitException;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_udiff;
use function array_values;
use function count;
use function explode;
use function filter_var;
use function http_build_query;
use function implode;
use function is_array;
use function is_object;
use function ltrim;
use function mb_strtolower;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function range;
use function sprintf;
use function trim;
use function urlencode;

use const FILTER_VALIDATE_INT;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Url Helper.
 */
class Url extends \yii\helpers\Url
{
    /**
     * Парсит параметры запроса из строки
     *
     * @param array|string|null $query
     * @return array параметры в виде массива
     */
    public static function parseQuery(array|string|null $query): array
    {
        if ($query === null || $query === '' || $query === []) {
            $query = [];
        } elseif (! is_array($query)) {
            $query = trim($query, " \t\n\r\0\x0B?");
            if ($query === '') {
                $query = [];
            } else {
                $parsed = [];
                parse_str($query, $parsed);
                $query = $parsed;
            }
        }

        return $query;
    }

    /**
     * Рекурсивное построение строки параметров.
     *
     * @param array $query
     * @param string $parentKey
     * @return string[]
     */
    private static function internalBuildQuery(array $query, string $parentKey = ''): array
    {
        // удаляет из параметров некорректные ключи и null-значения
        $filterParams = static function($params) use (&$filterParams): array {
            $filtered = [];

            // преобразуем query, удаляя некорректные ключи и null-значения
            foreach ((array)$params as $k => $v) {
                // пропускаем некорректные ключи
                if ((string)$k === '') {
                    continue;
                }

                // считаем значения null для удаления
                if ($v === null) {
                    continue;
                }

                if (is_array($v) || is_object($v)) {
                    $v = $filterParams($v);
                } else {
                    $v = (string)$v;
                }

                $filtered[$k] = $v;
            }

            return $filtered;
        };

        // проверяет является ли массив индексным списком параметров
        $isIndexed = static function($params): bool {
            $params = (array)$params;
            if (empty($params)) {
                return true;
            }

            $keys = [];
            foreach (array_keys($params) as $key) {
                $keys[] = filter_var($key, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 0]
                ]);
            }

            return $keys === range(0, count($params) - 1);
        };

        // кодирует ключ
        $urlEncodeKey = static fn(string $key): string => $key === '#' ? $key : urlencode($key);

        $query = $filterParams($query);
        $isIndexed = $parentKey !== '' && $isIndexed($query);
        $parts = [];

        foreach ($query as $k => $v) {
            if ($parentKey === '') {
                $key = $urlEncodeKey((string)$k);
            } else {
                $key = $isIndexed ? $parentKey . '[]' : $parentKey . '[' . $urlEncodeKey((string)$k) . ']';
            }

            if (is_array($v) || is_object($v)) {
                $v = (array)$v;
                if ($v === []) {
                    $parts[] = $key . '[]';
                } else {
                    // вместо медленного array_merge
                    foreach (static::internalBuildQuery($v, $key) as $p) {
                        $parts[] = $p;
                    }
                }
            } else {
                $parts[] = ((string)$v === '') ? $key : $key . '=' . urlencode((string)$v);
            }
        }

        return $parts;
    }

    /**
     * Конвертирует параметры запроса в строку
     *
     * @param object|array|string|null $query
     * @return string
     */
    public static function buildQuery(object|array|string|null $query): string
    {
        if ($query === null || $query === '' || $query === []) {
            $query = '';
        } elseif (is_array($query) || is_object($query)) {
            //$query = preg_replace(['~%5B~i', '~%5D~i', '~\[\d+\]~'], ['[', ']', '[]'], http_build_query($query));
            $parts = static::internalBuildQuery((array)$query);
            $query = implode('&', $parts);
        }

        return (string)$query;
    }

    /**
     * Фильтрует парамеры запроса, удаляя ключи с пустыми значениями.
     *
     * @param array|string|null $query
     * @return array
     */
    public static function filterQuery(array|string|null $query): array
    {
        $query = static::parseQuery($query);

        foreach ($query as $k => &$v) {
            if (is_array($v)) {
                $v = static::filterQuery($v);
            }

            if ($v === null || $v === '' || $v === []) {
                unset($query[$k]);
            }
        }

        return $query;
    }

    /**
     * Нормализует параметры запроса.
     * Конвертирует из строки в массив, сортирует по названию параметров.
     *
     * @param array|string|null $query
     * @return array
     */
    public static function normalizeQuery(array|string|null $query): array
    {
        $query = static::parseQuery($query);

        uksort($query, static function ($k1, $k2) {
            if ($k1 === '#') {
                return 1;
            }

            if ($k2 === '#') {
                return -1;
            }

            return (string)$k1 <=> (string)$k2;
        });

        foreach ($query as &$v) {
            $v = is_array($v) ? static::normalizeQuery($v) : (string)$v;
        }

        return $query;
    }

    /**
     * Выделяет из параметров utm- gclid, fbclid и roistat-метки.
     *
     * @param array $query параметры, из которых удаляется
     * @return array удаленные параметры
     */
    public static function extractTrackingParams(array &$query): array
    {
        $query = static::parseQuery($query);
        $extra = [];

        foreach ($query as $key => $val) {
            /** @noinspection SpellCheckingInspection */
            if (preg_match('~^(utm_|roistat|(g|y|fb)clid)~ui', (string)$key)) {
                $extra[$key] = $val;
                unset($query[$key]);
            }
        }

        return $extra;
    }

    /**
     * Удаляет tracking-параметры известных систем.
     *
     * @param array $query
     * @return array
     */
    public static function removeTrackingParams(array $query): array
    {
        static::extractTrackingParams($query);

        return $query;
    }

    /**
     * Выделяет общие (не ЧПУ) параметры
     *
     * @param array $query параметры, из которых удаляется
     * @return array выделенные параметры
     */
    public static function extractCommonParams(array &$query): array
    {
        $extra = static::extractTrackingParams($query);

        foreach ($query as $key => $val) {
            if (preg_match('~^(sort|page|limit)$~ui', (string)$key)) {
                $extra[$key] = $val;
                unset($query[$key]);
            }
        }

        return $extra;
    }

    /**
     * Удаляет общие (не ЧПУ) параметры.
     *
     * @param array $query
     * @return array
     */
    public static function removeCommonPrams(array $query): array
    {
        static::extractCommonParams($query);

        return $query;
    }

    /**
     * Вычитание параметров рекурсивно.
     * Из параметров args1 вычитаются параметры args2
     *
     * @param array|string|null $query1 параметры (уменьшаемое)
     * @param array|string|null $query2 параметры (вычитаемое)
     * @param array $options опции сравнения
     * - bool $noCase - игнорировать регистр при сравнении значений
     * @return array $query1 - $query2
     */
    public static function diffQuery(array|string|null $query1, array|string|null $query2, array $options = []): array
    {
        // если уменьшаемое пустое, то результат пустой
        $query1 = static::parseQuery($query1);
        if (empty($query1)) {
            return [];
        }

        // если вычитаемое пустое, то результат - вычитаемое
        $query2 = static::parseQuery($query2);
        if (empty($query2)) {
            return $query1;
        }

        // приводим к плоскому виду для сравнения
        $query1 = static::flatQuery($query1);
        $query2 = static::flatQuery($query2);

        // сравнение с регистром или без
        $noCase = ! empty($options['noCase']);

        $diff = array_udiff(
            $query1,
            $query2,
            static fn($v1, $v2): int => $noCase ?
                mb_strtolower((string)$v1) <=> mb_strtolower((string)$v2) : (string)$v1 <=> (string)$v2
        );

        // конвертируем в обычный формат и возвращаем
        return static::unflatQuery($diff);
    }

    /**
     * Преобразовывает многомерные данные параметров в плоский массив параметров.
     *
     * @param array|string|null $query парамеры запроса
     * @return string[] одномерный массив параметров в виде ["id=1", "a[]=2", "b[3][4]=5"]
     */
    public static function flatQuery(array|string|null $query): array
    {
        if ($query === null || $query === '' || $query === []) {
            return [];
        }

        $query = static::buildQuery($query);

        // разбиваем на компоненты по &
        return explode('&', $query) ?: [];
    }

    /**
     * Восстанавливает парамеры запроса из плоского вида.
     *
     * @param array $flatQuery
     * @return array
     */
    public static function unflatQuery(array $flatQuery): array
    {
        // объединяем компоненты по &
        return empty($flatQuery) ? [] : static::parseQuery(implode('&', $flatQuery));
    }

    /**
     * Конвертирует домен в ASCII IDN
     *
     * @param string $domain
     * @return string
     */
    public static function idnToAscii(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }

        return idn_to_ascii($domain, IDNA_DEFAULT);
    }

    /**
     * Нормализация пути
     * Удаляет лишние компоненты, заменяет корневой путь на пустой
     *
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        // сохраняем начальный и конечный слэши
        $startSlash = str_starts_with($path, '/');
        $endSlash = (mb_substr($path, -1, 1) === '/');

        // разбиваем путь на компоненты
        $path = array_values(preg_split('~/+~um', $path, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        $newPath = [];
        foreach ($path as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }

            if ($p === '..' && $startSlash) {
                array_pop($newPath);
            } else {
                $newPath[] = $p;
            }
        }

        $path = implode('/', $newPath);

        if ($startSlash) {
            $path = '/' . $path;
        }

        if ($endSlash && $path !== '/') {
            $path .= '/';
        }

        return $path;
    }

    /**
     * Проверяет имеет ли домен взаимоотношение родительский-дочерний с $domain
     *
     * @param string $dom1 домен
     * @param string $dom2 домен
     * @return bool true, если $dom1 == $dom2 или один из них является поддоменом другого
     */
    public static function isDomainsRelated(string $dom1, string $dom2): bool
    {
        $dom1 = static::normalizeHost($dom1);
        if (empty($dom1)) {
            throw new InvalidArgumentException('dom1');
        }

        $dom2 = static::normalizeHost($dom2);
        if (empty($dom2)) {
            throw new InvalidArgumentException('dom2');
        }

        if ($dom1 === $dom2) {
            return true;
        }

        $regex = '~.+?\.%s$~uism';

        return preg_match(sprintf($regex, preg_quote($dom1, '~')), $dom2) ||
            preg_match(sprintf($regex, preg_quote($dom2, '~')), $dom1);
    }

    /**
     * Нормализация имени домена, удаляет схему и путь
     * - удаляет пробелы
     * - преобразует в нижний регистр, IDN->UTF-8
     * - выделяет из ссылки, удаляя остальные компоненты
     * - удаляет www, ftp поддомены
     * - удаляет несколько разделителей "."
     *
     * @param string $name домен или ссылка
     * @return string
     * @throws InvalidArgumentException
     */
    public static function normalizeHost(string $name): string
    {
        // убираем все пробельные символы
        $name = preg_replace('~[\s\h\t\v\r\n]+~uim', '', trim($name));
        if ($name === '') {
            return $name;
        }

        // для корректного распознавания строки как домена, парсеру необходимо наличие протокола
        if (! preg_match('~^(\w+:)?//~um', $name)) {
            $name = '//' . $name;
        }

        // парсим имя домена
        $name = trim(parse_url($name, PHP_URL_HOST));
        if (empty($name)) {
            throw new InvalidArgumentException('domain name');
        }

        // преобразуем в нижний регистр и UTF-8
        $name = mb_strtolower(static::idnToUtf8($name));

        // разбиваем домен на компоненты
        $parts = preg_split('~\.+~um', $name, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            throw new InvalidArgumentException('domain name');
        }

        return implode('.', $parts);
    }

    /**
     * Конвертирует в IDN UTF-8
     *
     * @param string $domain
     * @return string
     */
    public static function idnToUtf8(string $domain): string
    {
        $domain = trim($domain);
        if ($domain === '') {
            return '';
        }

        return idn_to_utf8($domain, IDNA_DEFAULT);
    }

    /**
     * Проверяет является ли домен поддоменом другого
     *
     * @param string $domain
     * @param string $parent
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function isSubdomain(string $domain, string $parent): bool
    {
        $domain = static::normalizeHost($domain);
        if (empty($domain)) {
            throw new InvalidArgumentException('domain');
        }

        $parent = static::normalizeHost($parent);
        if (empty($parent)) {
            throw new InvalidArgumentException('parent');
        }

        return ! empty(static::getSubdomain($domain, $parent));
    }

    /**
     * Возвращает поддомен домена.
     * Пример:
     * "test.mail.ru", "mail.ru" => "test"
     * "mail.ru", "mail.ru" => ""
     * "test.mail.ru", "yandex.ru" => false
     *
     * @param string $domain домен
     * @param string $parent родительский
     * @return string|null string - имя поддомена, null - если $domain не является поддоменом родительского
     */
    public static function getSubdomain(string $domain, string $parent): ?string
    {
        $domain = static::normalizeHost($domain);
        if (empty($domain)) {
            throw new InvalidArgumentException('domain');
        }

        $parent = static::normalizeHost($parent);
        if (empty($parent)) {
            throw new InvalidArgumentException('parent');
        }

        $matches = null;
        if (! preg_match(sprintf('~^(?:(.+?)\.)?%s$~uism', preg_quote($parent, '~')), $domain, $matches)) {
            return null;
        }

        return $matches[1] ?? '';
    }

    /**
     * Редиректит на правильный URL страницы, если текущий не совпадает.
     *
     * @param array $url
     */
    public static function redirectIfNeed(array $url): void
    {
        // канонический url
        $needUrl = static::to(static::removeTrackingParams($url));

        // пересобираем текущий url запроса (не используем Request::pathInfo из-за глюка с urlencode)
        $currentUrl = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        // параметры запроса (нельзя брать в $_GET или в queryParams, потому как там добавлены параметры акции)
        $queryParams = static::parseQuery($_SERVER['QUERY_STRING']);

        // добавляем параметры запроса без UTM
        $extra = static::extractTrackingParams($queryParams);
        if (! empty($queryParams)) {
            // используем http_build_query, также как и Url::to
            $currentUrl .= '?' . http_build_query($queryParams);
        }

        // сравниваем получившиеся URL и переадресуем
        if ($currentUrl !== $needUrl) {
            // добавляем utm-метки каноническому url
            $redirectUrl = static::to(array_merge($url, $extra), true);

            try {
                Yii::$app->end(0, Yii::$app->response->redirect($redirectUrl, 301));
            } catch (ExitException $ex) {
                Yii::error($ex, __METHOD__);
                exit;
            }
        }
    }
}
