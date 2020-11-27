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
            'endpoint' => 'http://oss-cn-hangzhou.aliyuncs.com',
            'isCName' => false,
            'securityToken' => null,
            'requestProxy' => null,
        ],
        'qiniu' => [
            'accessKey' => '<yourAccessKey>',
            'secretKey' => '<yourAccessSecretKey>',
        ],
    ],
];
