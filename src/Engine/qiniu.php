<?php

namespace PhalApi\Xoss\Engine;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Qiniu
{
    protected $config;

    protected $client;

    public function __construct($config = null)
    {
        $di = \PhalApi\DI();
        $this->config = $config;
        if (is_null($this->config)) {
            $this->config = $di->config->get('app.Xoss.qiniu');
        }
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        try {
            $auth = new Auth($accessKey, $secretKey);
            $this->client = $auth;
        } catch (Exception $e) {
            $di->logger->error($e->getMessage());
        }
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function uploadFile($bucket, $object, $filePath, $options = null)
    {
        $di = \PhalApi\DI();
        if (!file_exists($filePath)) {
            $di->logger->error('Xoss.qiniu # uploadFile # file not exists', $filePath);

            return false;
        }
        // $config = new Config();
        // $bucketManager = new BucketManager($this->auth, $config);
        // if (!$this->client->doesBucketExist($bucket)) {
        //     $di->logger->error('Xoss.qiniu # uploadFile # Bucket not exists', $bucket);

        //     return false;
        // }
        try {
            $expires = 3600;
            $policy = null;
            $token = $this->auth->uploadToken($bucket, null, $expires, $policy, true);
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            if (null != $err) {
                $di->logger->error('Xoss.qiniu # uploadFile # error', $err);
            } else {
                return $ret;
            }
        } catch (Exception $e) {
            $di->logger->error('Xoss.qiniu # uploadFile', $e->getMessage());

            return false;
        }
    }

    public function getFileList($bucket, $prefix = '', $marker = '', $limit = 100, $delimiter = '/')
    {
        $di = \PhalApi\DI();
        try {
            list($ret, $err) = $bucketManager->listFiles($bucket, $prefix, $marker, $limit, $delimiter);
            if (null !== $err) {
                $di->logger->error('Xoss.qiniu # getFileList # error', $err);
            } else {
                return $ret;
            }
        } catch (Exception $e) {
            $di->logger->error('Xoss.qiniu # getFileList', $e->getMessage());

            return false;
        }
    }
}
