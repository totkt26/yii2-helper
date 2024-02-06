<?php
/*
 * @copyright 2019-2021 Dicr http://dicr.org
 * @author Igor A Tarasov <develop@dicr.org>
 * @license proprietary
 * @version 31.10.21 22:55:34
 */

declare(strict_types = 1);
namespace dicr\helper;

use Yii;
use yii\base\BootstrapInterface;
use yii\i18n\PhpMessageSource;

/**
 * Автозагрузка при настройке пакета.
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritDoc
     */
    public function bootstrap($app): void
    {
        // Трансляция
        $app->i18n->translations['dicr/helper'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'ru',
            'basePath' => __DIR__ . '/messages'
        ];

        // заменя классов
        Yii::$container->set(\yii\helpers\ArrayHelper::class, ArrayHelper::class);
        Yii::$container->set(\yii\bootstrap5\Html::class, Html::class);
        Yii::$container->set(\yii\helpers\Inflector::class, Inflector::class);
        Yii::$container->set(\yii\helpers\StringHelper::class, StringHelper::class);
        Yii::$container->set(\yii\helpers\Url::class, Url::class);
    }
}
