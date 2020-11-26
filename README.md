# PhalApi 2.x 的第三方OSS扩展
PhalApi 2.x扩展类库，支持Qiniu、Aliyun的OSS扩展。

## 安装和配置
修改项目下的composer.json文件，并添加：  
```
    "vivlong/phalapi-xoss":"dev-master"
```
然后执行```composer update```。  

安装成功后，添加以下配置到/path/to/phalapi/config/app.php文件：  
```php
    /**
     * OSS相关配置
     */
    'Xoss' =>  array(
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
            'secretKey'   => '<yourAccessSecretKey >',
        ),
    ),
```
并根据自己的情况修改填充。 

## 注册
在/path/to/phalapi/config/di.php文件中，注册：  
```php
$di->xOss = function() {
        return new \PhalApi\Xoss\Lite();
};
```

## 使用
第一种使用方式：上传本地文件：
```php
  \PhalApi\DI()->xOss->uploadFile($bucket, $object, $filePath);
```

