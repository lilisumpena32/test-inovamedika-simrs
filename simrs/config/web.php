<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Jakarta',
    'aliases' => [
        '@bower' => '@vendor/yidas/yii2-bower-asset/bower',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'vsMlrBvklaHkpT-_gFSYTS5cnH7FZyRn',
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
        ],
        'access' => [
            'class' => \app\components\Access::class,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST api/v1/user' => 'api/user/create',
                'PUT api/v1/user/<id:[\w\-]+>/password' => 'api/user/password',
                'PUT api/v1/user/<id:[\w\-]+>/status' => 'api/user/status',
                'PUT api/v1/user/<id:[\w\-]+>' => 'api/user/update',
                'DELETE api/v1/user/<id:[\w\-]+>' => 'api/user/delete',
                'GET api/v1/user/<id:[\w\-]+>' => 'api/user/view',
                'POST api/v1/role' => 'api/role/create',
                'PUT api/v1/role/<id:[\w\-]+>/access' => 'api/role/access',
                'PUT api/v1/role/<id:[\w\-]+>/status' => 'api/role/status',
                'PUT api/v1/role/<id:[\w\-]+>' => 'api/role/update',
                'PUT api/v1/karyawan/<id:[\w\-]+>/akun' => 'api/karyawan/akun',
                'api/v1/<controller:(satuan|kategori|golongan-obat)>' => 'api/<controller>/index',
                'POST api/v1/<controller:(pasien|poli|karyawan|jadwal|obat)>' => 'api/<controller>/create',
                'GET api/v1/<controller:(pasien|poli|karyawan|jadwal|obat)>/<id:[\w\-]+>' => 'api/<controller>/view',
                'PUT api/v1/<controller:(pasien|poli|karyawan|jadwal|obat)>/<id:[\w\-]+>/status' => 'api/<controller>/status',
                'PUT api/v1/<controller:(pasien|poli|karyawan|jadwal|obat)>/<id:[\w\-]+>' => 'api/<controller>/update',
                'api/v1/<controller:\w+>/<action:\w+>' => 'api/<controller>/<action>',
                'api/v1/<controller:\w+>' => 'api/<controller>/index',
            ],
        ],
    ],
    'modules' => [
        'api' => [
            'class' => \app\modules\api\Module::class,
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
