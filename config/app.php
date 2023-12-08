<?php

return [
    /*
     * 相关配置
     */
    'Xoss' => [
        'aliyun' => [
            'accessKeyId' => '<yourAccessKeyId>',
            'accessKeySecret' => '<yourAccessKeySecret>',
            'bucket' => '<yourBucketName>',
            'endpoint' => 'https://your-endpoint',
            'isCName' => false,
            'securityToken' => null,
            'requestProxy' => null,
        ],
        'qiniu' => [
            'accessKey' => '<yourAccessKey>',
            'secretKey' => '<yourAccessSecretKey>',
        ],
        'qcloud' => [
            'secretId' => '<yourAccessKey>',
            'secretKey' => '<yourAccessSecretKey>',
            'region' => 'ap-beijing',
            'schema' => 'https',
        ],
        'huaweicloud' => [
            'accessKeyId' => '<yourAccessKeyId>',
            'accessKeySecret' => '<yourSecretAccessKey>',
            'endpoint' => 'https://your-endpoint',
        ],
    ],
];
