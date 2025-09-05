<?php

namespace MarketforceInfo\SendGrid\Tests;

use Yii;
use yii\di\Container;
use yii\helpers\ArrayHelper;

/**
 *
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockWebApplication();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->destroyApplication();
    }

    /**
     * Mocks a web application instance using the specified configuration and application class.
     *
     * @param array $config The configuration array to override the default application configuration.
     * @param string $appClass The fully qualified name of the application class to instantiate. Defaults to '\yii\web\Application'.
     *
     * @return void This method does not return a value.
     */
    protected function mockWebApplication(array $config = [], string $appClass = '\yii\web\Application'): void
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'aliases' => [
                '@bower' => '@vendor/bower-asset',
                '@npm' => '@vendor/npm-asset',
            ],
            'components' => [
                'request' => [
                    'cookieValidationKey' => '2VYuNNIognPSVv0zqj1C9sdmgk_O1UBa',
                    'scriptFile' => __DIR__ . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
                'mailer' => [
                    'class' => 'nickcv\mandrill\Mailer',
                    'apikey' => 'YourApiKey',
                ],
                'log' => [
                    'traceLevel' => YII_DEBUG ? 3 : 0,
                    'targets' => [
                        [
                            'class' => 'yii\log\FileTarget',
                            'levels' => ['error', 'warning', 'info'],
                        ],
                    ],
                ],
                'urlManager' => [
                    'showScriptName' => true,
                ],
            ],
            'params' => [
                'adminEmail' => 'admin@example.com',
            ],
        ], $config));
//        FileHelper::createDirectory(Yii::getAlias('@yiiunit/extensions/mandrill/runtime'));
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication(): void
    {
        Yii::$app = null;
        Yii::$container = new Container();
//        FileHelper::removeDirectory(Yii::getAlias('@yiiunit/extensions/mandrill/runtime'));
    }
}
