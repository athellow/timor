<?php
declare (strict_types = 1);

namespace timor\services\workerman\services;

use think\worker\Server;
use Workerman\Worker;

use timor\services\workerman\callback\Callback;

class BaseService extends Server
{
    /**
     * @var array
     */
    protected $config = [];
    
    /**
     * 构造函数
     * 
     * @access public
     */
    public function __construct()
    {
        $this->config = config('worker_server');
        $config = $this->getConnConf();
        
        // 全局静态属性
        $staticConf = ['stdoutFile', 'daemonize', 'pidFile', 'logFile'];
        foreach ($staticConf as $name) {
            if (!empty($this->config[$name])) {
                Worker::${$name} = $this->config[$name];
            }
        }

        if (!empty($config['socket'])) {
            $this->socket = $config['socket'];
        } else {
            !empty($config['protocol']) && $this->protocol = $config['protocol'];
            !empty($config['host']) && $this->host = $config['host'];
            !empty($config['port']) && $this->port = $config['port'];
        }

        !empty($config['option']) && $this->option = $config['option'];
        !empty($config['context']) && $this->context = $config['context'];

        parent::__construct();
    }

    protected function getConnConf()
    {
        return [];
    }

    /**
     * 设置回调
    
     * @access public
     * @param Callback $callback
     */
    public function setCallBack(Callback $callback)
    {
        if (!is_null($this->worker)) {
            foreach ($this->event as $event) {
                if (method_exists($callback, $event)) {
                    $this->worker->$event = [$callback, $event];
                }
            }
        }
    }
}