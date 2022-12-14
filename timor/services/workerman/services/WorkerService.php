<?php
declare (strict_types = 1);

namespace timor\services\workerman\services;

use timor\services\workerman\callback\WorkerCallback;

class WorkerService extends BaseService
{
    /**
     * 获取连接配置
     * 
     * @access protected
     */
    protected function getConnConf()
    {
        return $this->config['worker'] ?? [];
    }

    /**
     * 初始化
     * 
     * @access protected
     */
    protected function init()
    {
        $callback = new WorkerCallback($this->worker);
        // 设置回调
        $this->setCallBack($callback);
    }
}