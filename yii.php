#!/usr/bin/env php
<?php
/**
 * Yii console bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require(__DIR__ . '/lib/vendor/autoload.php');
require(__DIR__ . '/lib/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/lib/common/config/bootstrap.php');
require(__DIR__ . '/lib/console/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/lib/common/config/main.php'),
    require(__DIR__ . '/lib/common/config/main-local.php'),
    require(__DIR__ . '/lib/console/config/main.php'),
    require(__DIR__ . '/lib/console/config/main-local.php')
);

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
