<?php

namespace PhalApi\Xoss\Engine;

use Qcloud\Cos\Client;

class Qcloud
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
      $this->config = $di->config->get('app.Xoss.qcloud');
    }
    if (!$this->config) {
      $di->logger->info(__CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, 'No engine config');

      return false;
    }
    $accessKey = $this->config['secretId'];
    $secretKey = $this->config['secretKey'];
    $region = $this->config['region'];
    $schema = $this->config['schema'];
    try {
      $cosClient = new Client([
        'region' => $region,
        'schema' => $schema,
        'credentials' => [
          'secretId'  => $accessKey,
          'secretKey' => $secretKey,
        ],
      ]);
      $this->client = $cosClient;
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
      $res = $this->client->upload($bucket, $object, fopen($filePath, 'rb'), $options);

      return $res;
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);

      return false;
    }
  }

  public function deleteObject($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (empty($object)) {
      return false;
    }
    if (!$this->checkObjectExist($bucket, $object)) return false;
    try {
      $res = $this->client->deleteObject([
        'Bucket' => $bucket,
        'Key' => $object,
      ]);

      return $res;
    } catch (\Exception  $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);

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
    } catch (\Exception $e) {
      $di->logger->error(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Exception' => $e->getMessage()]);

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
      $result = $this->client->doesBucketExist($bucket);

      return true;
    } catch (\Exception $e) {
      $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['Bucket not exists' => $bucket]);

      return false;
    }
  }

  private function checkObjectExist($bucket, $object)
  {
    $di = \PhalApi\DI();
    if (empty($object)) {
      return false;
    }
    if (!$this->client->doesObjectExist($bucket, $object)) {
      $di->logger->info(__NAMESPACE__ . DIRECTORY_SEPARATOR . __CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['file not exists' => $object]);

      return false;
    }

    return true;
  }
}
