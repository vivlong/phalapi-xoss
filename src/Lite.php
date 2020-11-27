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
     * 配置参数.
     *
     * @var type
     */
    private $config;

    /**
     * 获取引擎.
     *
     * @var string
     */
    private $engine;

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
    public function set($engine)
    {
        $di = \PhalApi\DI();
        $this->engine = strtolower($engine);
        $this->config = [];
        $config = $di->config->get('app.Xoss.'.$this->engine);
        if (!$config) {
            $di->logger->log('Xoss', 'No engine config', $this->engine);

            return false;
        }
        $this->config = array_merge($this->config, $config);
        $engine = '\\PhalApi\\Xoss\\Engine\\'.ucfirst(strtolower($this->engine));
        $this->client = new $engine($this->config);
        if (!$this->client) {
            $di->logger->log('Xoss', 'No engine class', $engine);

            return false;
        }

        return true;
    }
}
