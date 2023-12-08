<?php

namespace PhalApi\Xoss\Engine;

use Obs\ObsClient;

class Huaweicloud
{
  protected $config;
  protected $debug;
  protected $client;

  public function __construct($config = null)
  {
    $di = \PhalApi\DI();
    $this->debug = $di->debug;
    $this->config = $config;
    if (is_null($this->config)) {
      $this->config = $di->config->get('app.Xoss.huaweicloud');
    }
    if (!$this->config) {
      $di->logger->info(__CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, 'No engine config');

      return false;
    }
    $accessKey = $this->config['accessKeyId'];
    $secretKey = $this->config['accessKeySecret'];
    $endpoint = $this->config['endpoint'];
    try {
      $obsClient = new ObsClient([
        'key' => $accessKey,
        'secret' => $secretKey,
        'endpoint' => $endpoint,
        // 'ssl_verify' => false,
        // 'max_retry_count' => 1,
        // 'socket_timeout' => 20,
        // 'connect_timeout' => 20,
        // 'chunk_size' => 8196
      ]);
      $this->client = $obsClient;
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);
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
    if (empty($object)) {
      return false;
    }
    if (!$this->doesFilePathExist($filePath)) return false;
    if (!$this->doesBucketExist($bucket)) return false;
    try {
      $res = $this->client->putObject([
        'Bucket' => $bucket,
        'Key' => $object,
        // 'Body' => string / resource / GuzzleHttp\Psr7\StreamInterface
        'SourceFile' => $filePath,
      ]);

      return $res;
    } catch (\Obs\Common\ObsException $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getExceptionMessage()]);

      return false;
    }
  }

  public function getObject($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (empty($object)) {
      return false;
    }
    try {
      $res = $this->client->getObject([
        'Bucket' => $bucket,
        'Key' => $object,
      ]);

      return $res;
    } catch (\Obs\Common\ObsException $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['ObsException' => $e->getExceptionMessage()]);

      return false;
    }
  }

  public function deleteObject($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (empty($object)) {
      return false;
    }
    try {
      $res = $this->client->deleteObject([
        'Bucket' => $bucket,
        'Key' => $object,
      ]);

      return $res;
    } catch (\Obs\Common\ObsException $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['ObsException' => $e->getExceptionMessage()]);

      return false;
    }
  }

  public function deleteObjects($bucket, $objects)
  {
    $di = \PhalApi\DI();
    if (empty($objects)) {
      return false;
    }
    try {
      $res = $this->client->deleteObjects([
        'Bucket' => $bucket,
        'Objects' => $objects
      ]);

      return $res;
    } catch (\Obs\Common\ObsException $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['ObsException' => $e->getExceptionMessage()]);

      return false;
    }
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

  private function doesBucketExist($bucket)
  {
    $di = \PhalApi\DI();
    if (empty($bucket)) {
      return false;
    }
    try {
      $res = $this->client->headBucket([
        'Bucket' => $bucket,
      ]);

      return true;
    } catch (\Obs\Common\ObsException $obsException) {
      if ($obsException->getStatusCode() === 404) {
        $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Bucket not exists' => $bucket]);
      } else {
        $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['StatusCode' => $obsException->getStatusCode()]);
      }

      return false;
    }
  }
}
