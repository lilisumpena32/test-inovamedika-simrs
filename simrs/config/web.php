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
                'PUT api/v1/kunjungan/<id:[\w\-]+>/batal' => 'api/kunjungan/batal',
                'PUT api/v1/kunjungan/<id:[\w\-]+>/selesai' => 'api/pemeriksaan/selesai',
                'POST api/v1/kunjungan/<id:[\w\-]+>/pemeriksaan' => 'api/pemeriksaan/simpan',
                'GET api/v1/kunjungan/<id:[\w\-]+>/pemeriksaan' => 'api/pemeriksaan/lihat',
                'POST api/v1/kunjungan/<id:[\w\-]+>/tindakan' => 'api/pemeriksaan/tambah-tindakan',
                'GET api/v1/kunjungan/<id:[\w\-]+>/tindakan' => 'api/pemeriksaan/tindakan',
                'DELETE api/v1/kunjungan/<id:[\w\-]+>/tindakan/<detail:[\w\-]+>' => 'api/pemeriksaan/hapus-tindakan',
                'POST api/v1/kunjungan/<id:[\w\-]+>/lab' => 'api/pemeriksaan/tambah-lab',
                'GET api/v1/kunjungan/<id:[\w\-]+>/lab' => 'api/pemeriksaan/lab',
                'POST api/v1/kunjungan/<id:[\w\-]+>/resep' => 'api/resep/buat',
                'PUT api/v1/kunjungan/<id:[\w\-]+>/resep' => 'api/resep/ubah',
                'PUT api/v1/resep/<id:[\w\-]+>/serah' => 'api/resep/serah',
                'GET api/v1/farmasi/resep' => 'api/resep/daftar',
                'GET api/v1/kunjungan/<id:[\w\-]+>/resep' => 'api/resep/kunjungan',
                'GET api/v1/resep/<id:[\w\-]+>' => 'api/resep/lihat',
                'PUT api/v1/lab/<id:[\w\-]+>/hasil' => 'api/lab/hasil',
                'POST api/v1/lab/pull-hasil' => 'api/lab/pull-hasil',
                'GET api/v1/tindakan-list' => 'api/tindakan/list',
                'GET api/v1/jenis-lab-list' => 'api/jenis-lab/list',
                'GET api/v1/jenis-lab' => 'api/jenis-lab/index',
                'POST api/v1/jenis-lab' => 'api/jenis-lab/create',
                'GET api/v1/jenis-lab/<id:[\w\-]+>' => 'api/jenis-lab/view',
                'PUT api/v1/jenis-lab/<id:[\w\-]+>/status' => 'api/jenis-lab/status',
                'PUT api/v1/jenis-lab/<id:[\w\-]+>' => 'api/jenis-lab/update',
                'PUT api/v1/tagihan/<id:[\w\-]+>/bayar' => 'api/tagihan/bayar',
                'GET api/v1/kunjungan/jadwal-tersedia' => 'api/kunjungan/jadwal-tersedia',
                'api/v1/<controller:(satuan|kategori|golongan-obat)>' => 'api/<controller>/index',
                'GET api/v1/tagihan' => 'api/tagihan/index',
                'GET api/v1/tagihan/<id:[\w\-]+>' => 'api/tagihan/view',
                'POST api/v1/tagihan' => 'api/tagihan/create',
                'POST api/v1/<controller:(pasien|poli|karyawan|jadwal|obat|kunjungan)>' => 'api/<controller>/create',
                'GET api/v1/<controller:(pasien|poli|karyawan|jadwal|obat|kunjungan)>/<id:[\w\-]+>' => 'api/<controller>/view',
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
