<?php
declare (strict_types = 1);

namespace timor\services\pay;

use think\Container;
use think\facade\Config;
use think\helper\Str;

class Pay
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * 配置
     * @var array
     */
    protected $config = [];
    
    /**
     * 驱动
     * @var array
     */
    protected $drivers = [];

    /**
     * @var string
     */
    protected $namespace = '\\timor\\services\\pay\\support\\';

    /**
     * Pay constructor
     * @param string $name驱动名称
     * @param array $config 配置
     */
    public function __construct($name = null, array $config = [])
    {
        $this->name = $name ?: $this->getDefaultDriver();

        $this->config = $config;
    }

    /**
     * 默认驱动
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('pay.default', 'wechat_pay');
    }

    /**
     * 动态调用
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->driver()->{$method}(...$arguments);
    }

    /**
     * 获取驱动实例
     * @param null|string $name
     * @return mixed
     */
    protected function driver(string $name = null)
    {
        $name = $name ?: $this->name;

        return $this->drivers[$name] ?? $this->createDriver($name);
    }

    /**
     * 创建驱动
     *
     * @param string $name
     * @return mixed
     */
    protected function createDriver(string $name)
    {
        if ($this->namespace || false !== strpos($name, '\\')) {
            $class = false !== strpos($name, '\\') ? $name : $this->namespace . Str::studly($name);

            if (!class_exists($class)) {
                throw new \RuntimeException('class not exists: ' . $class);
            }
            
            $handle = Container::getInstance()->invokeClass($class, [$this->config]);

            return $handle;
        }

        throw new \InvalidArgumentException("Driver [$name] not supported.");
    }
}
