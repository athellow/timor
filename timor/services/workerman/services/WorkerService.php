<?php
declare (strict_types = 1);

namespace timor\services\workerman\services;

use think\worker\Server;
use timor\services\workerman\WorkermanService;
use Workerman\Worker;

class WorkerService extends Server
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
        $config = $this->config['worker'] ?? [];
        
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
            $this->protocol = $config['protocol'];
            $this->host = $config['host'];
            $this->port = $config['port'];
        }

        $this->option = $config['option'] ?? [];
        $this->context = $config['context'] ?? [];

        parent::__construct();
    }

    /**
     * 初始化
     * 
     * @access protected
     */
    protected function init()
    {
        if (!is_null($this->worker)) {
            $server = new WorkermanService($this->worker);

            // 设置回调
            foreach ($this->event as $event) {
                if (method_exists($server, $event)) {
                    $this->worker->$event = [$server, $event];
                }
            }
        }
    }
}