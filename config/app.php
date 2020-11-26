<?php

return array(
    /**
     * 相关配置
     */
    'Xoss' => array(
        'aliyun' => array(
            'accessKeyId'       => '<yourAccessKeyId>',
            'accessKeySecret'   => '<yourAccessKeySecret>',
            'bucket'            => '<yourBucketName>',
            'endpoint'          => 'http://oss-cn-hangzhou.aliyuncs.com',
            'isCName'           => false,
            'securityToken'     => null,
            'requestProxy'      => null,
        ),
        'qiniu' =>  array(
            'accessKey'   => '<yourAccessKey>',
            'secretKey'   => '<yourAccessSecretKey>',
        ),
    ),
);
