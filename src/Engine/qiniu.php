<?php

namespace PhalApi\Xoss\Engine;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Qiniu
{
    protected $config;

    protected $auth;

    public function __construct($config = null)
    {
        $di = \PhalApi\DI();
        $this->config = $config;
        if (null == $this->config) {
            $this->config = $di->config->get('app.Xoss.qiniu');
        }
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        try {
            $auth = new Auth($accessKey, $secretKey);
            $this->auth = $auth;
        } catch (Exception $e) {
            $di->logger->error('Xoss.qiniu # Auth Exception', $e->getMessage());
        }
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getConfig()
    {
        return $this->config;
    }

    private function doesBucketExist($bucket)
    {
        if (empty($bucket)) {
            return false;
        }
        $config = new Config();
        $bucketManager = new BucketManager($this->auth, $config);
        list($ret, $err) = $bucketManager->bucketInfo($bucket);
        if (null != $err) {
            return false;
        } else {
            return true;
        }
    }

    private function doesObjectExist($bucket, $object)
    {
        if (empty($bucket) || empty($object)) {
            return false;
        }
        $config = new Config();
        $bucketManager = new BucketManager($this->auth, $config);
        list($ret, $err) = $bucketManager->bucketInfo($bucket);
        if (null != $err) {
            return false;
        }
        list($ret, $err) = $bucketManager->stat($bucket, $object);
        if (null != $err) {
            return false;
        } else {
            return true;
        }
    }

    public function uploadFile($bucket, $object, $filePath, $options = null)
    {
        $di = \PhalApi\DI();
        if (!file_exists($filePath)) {
            $di->logger->error('Xoss.qiniu # uploadFile # file not exists', $filePath);

            return false;
        }
        if (!$this->doesBucketExist($bucket)) {
            $di->logger->error('Xoss.qiniu # uploadFile # Bucket not exists', $bucket);

            return false;
        }
        try {
            $expires = 3600;
            $policy = null;
            $token = $this->auth->uploadToken($bucket, null, $expires, $policy, true);
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $object, $filePath);
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

    public function listObjects($bucket, $prefix = '', $delimiter = '/', $limit = 100, $marker = '')
    {
        $di = \PhalApi\DI();
        $config = new Config();
        $bucketManager = new BucketManager($this->auth, $config);
        try {
            list($ret, $err) = $bucketManager->listFiles($bucket, $prefix, $marker, $limit, $delimiter);
            if (null !== $err) {
                $di->logger->error('Xoss.qiniu # getFileList # error', $err);
            } else {
                return [
                    'object' => $ret['items'],
                    'directory' => $ret['commonPrefixes'],
                ];
            }
        } catch (Exception $e) {
            $di->logger->error('Xoss.qiniu # getFileList', $e->getMessage());

            return false;
        }
    }

    public function deleteObject($bucket, $object)
    {
        $di = \PhalApi\DI();
        if (!$this->doesObjectExist($bucket, $object)) {
            $di->logger->error('Xoss.qiniu # deleteObject # Bucket or file not exists', $object);

            return true;
        }
        $config = new Config();
        $bucketManager = new BucketManager($this->auth, $config);
        list($ret, $err) = $bucketManager->delete($bucket, $object);
        if (null !== $err) {
            $di->logger->error('Xoss.qiniu # deleteObject', $err);

            return false;
        }

        return true;
    }
}
