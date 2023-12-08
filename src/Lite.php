<?php

namespace PhalApi\Xoss;

class Lite
{
  /**
   * 实例.
   *
   * @var object
   */
  private $client;

  /**
   * 获取引擎.
   *
   * @var string
   */
  private $engine;

  public function __construct($engine = null, $config = null)
  {
    if (null != $engine) {
      $this->set($engine, $config);
    }
  }

  public function __call($method, $arguments)
  {
    if (method_exists($this, $method)) {
      return call_user_func_array([&$this, $method], $arguments);
    } elseif (!empty($this->client) && $this->client && method_exists($this->client, $method)) {
      return call_user_func_array([&$this->client, $method], $arguments);
    }
  }

  /**
   * 设置配置信息.
   *
   * @param string $engine 要使用的引擎
   * @param array  $config 配置
   */
  public function set($engine, $config = null)
  {
    $di = \PhalApi\DI();
    $this->engine = strtolower($engine);
    $engine = __NAMESPACE__ . '\\Engine\\' . ucfirst(strtolower($this->engine));
    $this->client = new $engine($config);
    if (!$this->client) {
      $di->logger->info(__CLASS__ . DIRECTORY_SEPARATOR . __FUNCTION__, ['No engine class' => $this->engine]);

      return false;
    }

    return true;
  }
}
