<?php

namespace PhalApi\Xoss\Engine;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

class Qiniu
{
  protected $config;
  protected $debug;
  protected $auth;
  protected $bucketManager;

  public function __construct($config = null)
  {
    $di = \PhalApi\DI();
    $this->debug = $di->debug;
    $this->config = $config;
    if (is_null($this->config)) {
      $this->config = $di->config->get('app.Xoss.qiniu');
    }
    if (!$this->config) {
      $di->logger->info(__CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, 'No engine config');

      return false;
    }
    $accessKey = $this->config['accessKey'];
    $secretKey = $this->config['secretKey'];
    try {
      $auth = new Auth($accessKey, $secretKey);
      $this->auth = $auth;
      $config = new Config();
      $this->bucketManager = new BucketManager($auth, $config);
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);
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

  public function getToken($bucket, $policy = null)
  {
    $di = \PhalApi\DI();
    if (!$this->doesBucketExist($bucket)) return false;
    try {
      $expires = 3600;
      $token = $this->auth->uploadToken($bucket, null, $expires, $policy, true);

      return $token;
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);

      return false;
    }
  }

  private function doesBucketExist($bucket)
  {
    $di = \PhalApi\DI();
    if (empty($bucket)) {
      return false;
    }
    list($ret, $err) = $this->bucketManager->bucketInfo($bucket);
    if (null != $err) {
      $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Bucket not exists' => $bucket]);

      return false;
    } else {
      return true;
    }
  }

  private function doesObjectExist($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (empty($bucket) || empty($object)) {
      return false;
    }
    list($ret, $err) = $this->bucketManager->bucketInfo($bucket);
    if (null != $err) {
      return false;
    }
    list($ret, $err) = $this->bucketManager->stat($bucket, $object);
    if (null != $err) {
      $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Bucket or file not exists' => $object]);
      return false;
    }

    return true;
  }

  public function uploadFile($bucket, $object, $filePath, $policy = null, $resumeFile = null, $version = 'v1', $partSize = 4 * 1024 * 1024)
  {
    $di = \PhalApi\DI();
    if (empty($object)) {
      return false;
    }
    if (!$this->doesFilePathExist($filePath)) return false;
    if (!$this->doesBucketExist($bucket)) return false;
    try {
      $expires = 3600;
      $token = $this->auth->uploadToken($bucket, null, $expires, $policy, true);
      $uploadMgr = new UploadManager();
      list($ret, $err) = $uploadMgr->putFile($token, $object, $filePath, null, 'application/octet-stream', false, $resumeFile, $version, $partSize);
      if (null != $err) {
        $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);
      } else {
        return $ret;
      }
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);

      return false;
    }
  }

  public function listObjects($bucket, $prefix = '', $delimiter = '/', $limit = 100, $marker = '')
  {
    $di = \PhalApi\DI();
    if (empty($bucket)) {
      return false;
    }
    if (!$this->doesBucketExist($bucket)) return false;
    try {
      list($ret, $err) = $this->bucketManager->listFiles($bucket, $prefix, $marker, $limit, $delimiter);
      if (null !== $err) {
        $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);
      } else {
        return [
          'object' => $ret['items'],
          'directory' => $ret['commonPrefixes'],
        ];
      }
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);

      return false;
    }
  }

  public function deleteObject($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (!$this->doesObjectExist($bucket, $object)) return false;
    list($ret, $err) = $this->bucketManager->delete($bucket, $object);
    if (null !== $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return true;
  }

  public function privateDownload($baseUrl)
  {
    $di = \PhalApi\DI();
    $signedUrl = $this->auth->privateDownloadUrl($baseUrl);

    return $signedUrl;
  }

  public function getStat($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (empty($bucket) || empty($object)) return false;
    list($ret, $err) = $this->bucketManager->stat($bucket, $object);
    if (null != $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return $ret;
  }

  public function changeMime($bucket, $object, $mime)
  {
    $di = \PhalApi\DI();
    if (!$this->doesObjectExist($bucket, $object)) return false;
    list($ret, $err) = $this->bucketManager->changeMime($bucket, $object, $mime);
    if (null != $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return true;
  }

  public function move($srcBucket, $srcKey, $destBucket, $destKey)
  {
    $di = \PhalApi\DI();
    if (!$this->doesObjectExist($srcBucket, $srcKey)) return false;
    list($ret, $err) = $this->bucketManager->move($srcBucket, $srcKey, $destBucket, $destKey, true);
    if (null != $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return true;
  }

  public function copy($srcBucket, $srcKey, $destBucket, $destKey)
  {
    $di = \PhalApi\DI();
    if (!$this->doesObjectExist($srcBucket, $srcKey)) return false;
    list($ret, $err) = $this->bucketManager->copy($srcBucket, $srcKey, $destBucket, $destKey, true);
    if (null != $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return true;
  }

  public function listDomain($bucket)
  {
    $di = \PhalApi\DI();
    if (!$this->doesBucketExist($bucket)) return false;
    list($ret, $err) = $this->bucketManager->domains($bucket);
    if (null != $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return $ret;
  }

  public function batchDelete($bucket, $objectKeys)
  {
    $di = \PhalApi\DI();
    if (!$this->doesBucketExist($bucket)) return false;
    $ops = $this->bucketManager->buildBatchDelete($bucket, $objectKeys);
    list($ret, $err) = $this->bucketManager->batch($ops);
    if (null !== $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return true;
  }

  public function batchMove($bucket, $keyPairs)
  {
    $di = \PhalApi\DI();
    if (!$this->doesBucketExist($bucket)) return false;
    $ops = $this->bucketManager->buildBatchMove($bucket, $keyPairs, $bucket, true);
    list($ret, $err) = $this->bucketManager->batch($ops);
    if (null !== $err) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Error' => $err]);

      return false;
    }

    return true;
  }

  private function doesFilePathExist($filePath)
  {
    $di = \PhalApi\DI();
    if (empty($filePath)) {
      return false;
    }
    if (!file_exists($filePath)) {
      $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['file not exists' => $filePath]);

      return false;
    }

    return true;
  }
}
