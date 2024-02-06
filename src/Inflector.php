<?php
/*
 * @copyright 2019-2022 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 04.01.22 18:48:10
 */

declare(strict_types = 1);

namespace dicr\helper;

use Yii;
use yii\base\InvalidArgumentException;

use function array_key_exists;
use function array_keys;
use function array_values;
use function explode;
use function idate;
use function implode;
use function is_array;
use function is_object;
use function is_scalar;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_replace;
use function time;
use function trim;

/**
 * Class Inflector
 */
class Inflector extends \yii\helpers\Inflector
{
    /**
     * @var string[] соответствие символов транслитерации
     * @link https://yandex.ru/support/nmaps/app_transliteration.html
     */
    public const LETTERS = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh',
        'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];

    /** @var string[] дни недели (0 - Пн) */
    public const WEEKDAYS = [
        'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'
    ];

    /** @var string[] короткие месяцы */
    public const MONTH_SHORT = [
        'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июль', 'авг', 'сен', 'окт', 'ноя', 'дек'
    ];

    /** @var string[] длинные месяцы (с большой буквы, чтобы "май" не совпадал в словаре с коротким названием) */
    public const MONTH_LONG = [
        'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь',
        'Декабрь'
    ];

    /** @var string[] родительный падеж месяцев */
    public const MONTH_GENITIVE = [
        'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября',
        'декабря'
    ];

    /**
     * Создает ЧПУ.
     *
     * @param string $string
     * @param string $replacement
     * @param bool $lowercase
     * @return string
     */
    public static function slug($string, $replacement = '-', $lowercase = true): string
    {
        // очищаем специальные символы и пробелы
        $string = trim(preg_replace('~[[:cntrl:]]|[\x00-\x1F\x7F\xA0\s\h\t\v\r\n]+~uim', ' ', $string));
        if ($string === '') {
            return '';
        }

        // конвертируем в нижний реестр
        if ($lowercase) {
            $string = mb_strtolower($string);
        }

        // транслитерация русских букв
        $string = (string)str_replace(
            array_keys(self::LETTERS), array_values(self::LETTERS), $string
        );

        // подстановка известных символов
        $string = (string)str_replace(['+', '@'], ['plus', 'at'], $string);

        // заменяем все, которые НЕ разрешены
        $string = preg_replace('~[^a-z0-9\-_.\~]+~uim', '-', $string);

        // удаляем подстановочные в начале, в конце и задвоения
        $string = preg_replace('~(^-)|(-$)|(-{2,})~um', '', $string);

        // заменяем подстановочные на заданные
        if ($replacement !== '-') {
            $string = (string)str_replace('-', $replacement, $string);
        }

        return $string;
    }

    /**
     * Форма слова для количества единиц.
     *
     * @param int|string $count кол-во единиц, например "123"
     * @param string $one единичная форма, например "товар"
     * @param string $two форма значения 2, например: "товара"
     * @param string $five форма значения 5, например: "товаров"
     * @return string форма для количества $count
     */
    public static function numDeclension(int|string $count, string $one, string $two, string $five): string
    {
        $count = (int)$count;
        $word = null;

        if ($count < 5 || $count > 20) {
            $mod = $count % 10;
            if ($mod === 1) {
                $word = $one;
            } elseif ($mod === 2 || $mod === 3 || $mod === 4) {
                $word = $two;
            }
        }

        return $word ?? $five;
    }

    /**
     * Количество товаров.
     *
     * @param int|string $count
     * @return string
     */
    public static function numProds(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'товар'),
            Yii::t('dicr/helper', 'товара'),
            Yii::t('dicr/helper', 'товаров')
        );
    }

    /**
     * Форма кол-ва моделей.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numModels(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'модель'),
            Yii::t('dicr/helper', 'модели'),
            Yii::t('dicr/helper', 'моделей')
        );
    }

    /**
     * Количественная форма отзывов.
     *
     * @param int|string $count
     * @return string
     */
    public static function numReviews(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'отзыв'),
            Yii::t('dicr/helper', 'отзыва'),
            Yii::t('dicr/helper', 'отзывов')
        );
    }

    /**
     * Количественная форма магазинов.
     *
     * @param int|string $count
     * @return string
     */
    public static function numShops(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'магазин'),
            Yii::t('dicr/helper', 'магазина'),
            Yii::t('dicr/helper', 'магазинов')
        );
    }

    /**
     * Форма кол-ва минут.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numMinutes(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'минута'),
            Yii::t('dicr/helper', 'минуты'),
            Yii::t('dicr/helper', 'минут')
        );
    }

    /**
     * Форма кол-ва часов.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numHours(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'час'),
            Yii::t('dicr/helper', 'часа'),
            Yii::t('dicr/helper', 'часов')
        );
    }

    /**
     * Форма кол-ва дней.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numDays(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'день'),
            Yii::t('dicr/helper', 'дня'),
            Yii::t('dicr/helper', 'дней')
        );
    }

    /**
     * Форма кол-ва недель.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numWeeks(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'неделя'),
            Yii::t('dicr/helper', 'недели'),
            Yii::t('dicr/helper', 'недель')
        );
    }

    /**
     * Форма кол-ва месяцев.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numMonthes(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'месяц'),
            Yii::t('dicr/helper', 'месяца'),
            Yii::t('dicr/helper', 'месяцев')
        );
    }

    /**
     * Форма кол-ва лет.
     *
     * @param int|string $count количество
     * @return string форма слова
     */
    public static function numYears(int|string $count): string
    {
        return static::numDeclension($count,
            Yii::t('dicr/helper', 'год'),
            Yii::t('dicr/helper', 'года'),
            Yii::t('dicr/helper', 'лет')
        );
    }

    /**
     * Форматирует срок в днях.
     *
     * @param int $days срок дней (0 - сегодня, 1 - завтра)
     * @return string текстовое представление
     */
    public static function daysTerm(int $days): string
    {
        if ($days < 0) {
            throw new InvalidArgumentException('days');
        }

        if ($days === 0) {
            return Yii::t('dicr/helper', 'сегодня');
        }

        if ($days === 1) {
            return Yii::t('dicr/helper', 'завтра');
        }

        if ($days === 2) {
            return Yii::t('dicr/helper', 'послезавтра');
        }

        if ($days === 7) {
            return Yii::t('dicr/helper', 'через') . ' ' .
                Yii::t('dicr/helper', 'неделю');
        }

        if ($days === 14 || $days === 21) {
            return Yii::t('dicr/helper', 'через') . ' ' .
                ($days / 7) . ' ' . Yii::t('dicr/helper', 'недели');
        }

        if ($days === (int)idate('t')) {
            return Yii::t('dicr/helper', 'через') . ' ' .
                Yii::t('dicr/helper', 'месяц');
        }

        if ($days === 61) {
            return Yii::t('dicr/helper', 'через') . ' 2 ' .
                Yii::t('dicr/helper', 'месяца');
        }

        if ($days <= 30) {
            return Yii::t('dicr/helper', 'через') . ' ' . $days . ' ' .
                static::numDays($days);
        }

        $time = time() + 86400 * $days;

        // получаем день и месяц
        return idate('d', $time) . ' ' .
            mb_strtolower(
                Yii::t('dicr/helper', self::MONTH_GENITIVE[idate('m', $time) - 1])
            );
    }

    /**
     * Группирует список дней недели
     *
     * @param int[] $days массив дней, в формате:
     *    [0, 1, 2, 4, 7]
     *
     * @return string[] группы дней в формате:
     *    ['Пн-Ср', 'Пт', 'Вс']
     */
    public static function groupDays(array $days): array
    {
        $groupDays = [];
        $startDay = null;
        $endDay = null;

        foreach ($days as $day) {
            // если это первый день, то формируем новый период
            if (! isset($endDay) || $day !== $endDay + 1) {
                if (isset($startDay)) {
                    // новый день не следует за предыдущим - сохраняем прошлый период
                    $group = Yii::t('dicr/helper', self::WEEKDAYS[$startDay]);
                    if ($endDay > $startDay) {
                        $group .= '-' . Yii::t('dicr/helper', self::WEEKDAYS[$endDay]);
                    }

                    $groupDays[] = $group;
                }

                // начинаем новый период
                $startDay = $day;
            }

            $endDay = $day;
        }

        // закрываем последний период
        if (isset($startDay)) {
            $group = Yii::t('dicr/helper', self::WEEKDAYS[$startDay]);
            if ($endDay > $startDay) {
                $group .= '-' . Yii::t('dicr/helper', self::WEEKDAYS[$endDay]);
            }

            $groupDays[] = $group;
        }

        return $groupDays;
    }

    /**
     * Конвертирует график работы в короткий формат.
     *
     * @param ?array $schedule график работы в формате:
     *   [
     *     0 => ['09:00', '18:00'],
     *     1 => ['09:00', '18:00'],
     *     ..
     *     5 => ['11:00', '16:00'],
     *     6 => null
     *   ]
     *
     * @return string[] короткий график работы в формате:
     *   [
     *     'Пн-Пт' => '09:00 - 18:00',
     *     'Сб' => '11:00 - 16:00',
     *     'Вс' => 'выходной'
     *   ]
     */
    public static function shortSchedule(?array $schedule): array
    {
        if (empty($schedule)) {
            return [];
        }

        /** @var array рабочие дни */
        $workdays = [];

        /** @var int[] выходные дни workTime => [days] */
        $holidays = [];

        // форматируем время работы всех дней
        for ($i = 0; $i < 7; $i++) {
            $workTime = $schedule[$i] ?? null;

            // выходной день
            if (empty($workTime)) {
                $holidays[] = $i;
                continue;
            }

            // форматируем рабочее время
            $workdays[$workTime[0] . ' - ' . $workTime[1]][] = $i;
        }

        $schedule = [];

        // группируем рабочие дни
        foreach ($workdays as $workTime => $days) {
            $days = implode(',', static::groupDays($days));
            $schedule[$days] = $workTime;
        }

        // группируем выходные
        if (! empty($holidays)) {
            $days = implode(',', static::groupDays($holidays));
            $schedule[$days] = Yii::t('dicr/helper', 'выходной');
        }

        return $schedule;
    }

    /**
     * Возвращает значение переменной.
     *
     * Переменные могут быть как простыми, так и сложными типами. Например:
     *
     * ```php
     * $vars = [
     *    'color' => 'red',
     *    'prod' => [
     *       'name' => 'Чайник',
     *       'price' => 256
     *    ]
     * ];
     * ```
     *
     * Путь переменной должен состоять из ключей, разделенных "|" и быть до конца (до скалярного значения):
     *
     * Например,
     *   - 'color' вернет 'red',
     *   - 'prod|name' вернет 'Чайник',
     *   - 'prod' вернет null, потому как значение не скалярное
     *
     * Также ключи пути могут содержать названия фильтров:
     *   - 'color|ucfirst' => 'Red',
     *   - 'prod|name|lower' => 'чайник'
     *
     * @param array $vars массив переменных, откуда берутся значения, например ['prod' => ['name' => 'Утюг']]
     * @param string $path путь значения, например 'prod|name|lower'
     * @return ?string значение или null если не найдено
     */
    public static function varValue(array $vars, string $path): ?string
    {
        if ($path === '' || empty($vars)) {
            return null;
        }

        // поддерживаемые фильтры
        $filters = [
            'trim' => static fn($val): string => trim((string)$val),
            'esc' => static fn($val): string => Html::esc((string)$val),
            'lower' => static fn($val): string => mb_strtolower((string)$val),
            'upper' => static fn($val): string => mb_strtoupper((string)$val),
            'ucfirst' => static fn($val): string => StringHelper::mb_ucfirst((string)$val),
            'lcfirst' => static fn($val): string => StringHelper::mb_lcfirst((string)$val),
            'asInteger' => static fn($val): string => Yii::$app->formatter->asInteger($val),
            'asCurrency' => static fn($val): string => Yii::$app->formatter->asCurrency($val),
            'asDate' => static fn($val): string => Yii::$app->formatter->asDate($val),
            'asTime' => static fn($val): string => Yii::$app->formatter->asTime($val),
            'asDatetime' => static fn($val): string => Yii::$app->formatter->asDatetime($val),
        ];

        // получаем путь ключей
        $keys = explode('|', $path) ?: [];

        /** @var mixed $value текущее значение */
        $value = $vars;

        // обходим ключи по порядку
        foreach ($keys as $key) {
            if (array_key_exists($key, $filters)) { // фильтр
                $value = $filters[$key]($value);
            } elseif (is_array($value)) { // массив
                $value = $value[$key] ?? null;
            } elseif (is_object($value)) { // объект
                $value = $value->{$key} ?? null;
            } else {
                $value = null;
            }

            // если значение не найдено, то прекращаем поиск
            if ($value === null) {
                break;
            }
        }

        // если значение не скалярное, значит путь был не до конца
        if ($value !== null && ! is_scalar($value)) {
            //Log::debug('Путь "' . $path . '" не до конца, значение не скалярное');
            $value = null;
        }

        return $value !== null ? (string)$value : null;
    }

    /**
     * Замена переменных в блоке текста.
     *
     * Переменные в тексте размечается как ${{путь}}.
     * Путь это путь переменной в `vars`.
     *
     * @see #getVarValue
     *
     * Если задан cleanText=true, то блок очищается, если в нем не найдены значения для каких-то переменных.
     * Если задан cleanVars=true, то ненайденные переменные в тексте заменяются пустой строкой "".
     *
     * @param string $text блок текста
     * @param array $vars значения переменных
     * @param array $opts опции
     * - bool $cleanText очищать весь текст, если не имеется ненайденные переменные
     * - bool $cleanVars очищать ненайденные переменные
     * @return string
     */
    public static function replaceBlockVars(string $text, array $vars = [], array $opts = []): string
    {
        // если текст пустой или нет переменных и опций очистки то возвращаем исходный текст без обработки
        if ($text === '' || (empty($vars) && empty($opts))) {
            return $text;
        }

        // регулярное выражение
        $regex = '~\$\{([^\}]*)\}~um';

        // подменяем переменные в блоке текста
        $text = preg_replace_callback($regex, static function(array $matches) use ($vars): string {
            $value = static::varValue($vars, $matches[1]);

            // если значение не найдено, то возвращаем шаблон без изменения
            return (string)($value ?? $matches[0]);
        }, $text);

        // очищаем весь текст если имеются ненайденные переменные
        if (! empty($opts['cleanText']) && preg_match($regex, $text)) {
            return '';
        }

        // удаление ненайденных переменных
        if (! empty($opts['cleanVars'])) {
            $text = (string)preg_replace($regex, '', $text);
        }

        // заменяем несколько горизонтальных пробелов одним
        $text = (string)preg_replace('~[\h\t]+~uim', ' ', $text);

        // удаляем пробелы и переносы перед знаками препинания
        $text = (string)preg_replace('~[\s\h\t\v\r\n]+([\,\;\.\!\?])~um', '$1', $text);

        // удаляем пробелы перед переносом строки
        return (string)preg_replace('~[\h\t]+([\v\r\n])~um', '$1', $text);
    }

    /**
     * Подмена переменных в блоках текста.
     *
     * Текст размечается на блоки: [[блок текста]].
     * Например: "Купить товар. [[Цвет товара: ${prod|attr|size}]].
     * Если в тексте не размечены блоки, то весь текст считается одним блоком.
     *
     * ```php
     * Html::replaceVars("Купить ${prod|name}", [
     *   'prod' => ['name' => "Автомобиль"]
     * ]);
     * ```
     *
     * Также можно применять фильтры:
     *
     * ```php
     * Html::replaceVars("Купить ${title|trim|lower|esc}", [
     *   'title' => 'Значение строки'
     * ]);
     * ```
     *
     * Начало и конец блока размечается как '[[текст с переменными, например ${prod|name}]]'. Если в тексте не найдена
     * разметка блоков, то весь текст считается одним блоком.
     *
     * @param ?string $string строка с переменными
     * @param array $vars значения для подмены (многомерный массив объектов)
     * @param array $opts опции
     * - bool $cleanText - удалять блок текста, если есть ненайденные переменные
     * - bool $cleanVars - удалять ненайденные переменные
     * @return string
     */
    public static function replaceVars(?string $string, array $vars = [], array $opts = []): string
    {
        // пропускаем пустые строки и если не нужно ничего заменять и очищать
        $string = (string)$string;
        if ($string === '' || (empty($vars) && empty($opts))) {
            return $string;
        }

        // если в тексте не размечены блоки, то весь текст оборачиваем в один блок
        if (! preg_match('~\[\[.*?\]\]~usm', $string)) {
            $string = '[[' . $string . ']]';
        }

        // обрабатываем блоки текста
        $string = preg_replace_callback('~\[\[(.*?)\]\]~usm', static fn(array $matches) => static::replaceBlockVars(
            $matches[1], $vars, $opts
        ), $string);

        // заменяем несколько горизонтальных пробелов одним
        return (string)preg_replace('~[\h\t]+~uim', ' ', $string);
    }
}
