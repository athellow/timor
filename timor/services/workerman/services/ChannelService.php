<?php
declare (strict_types = 1);

namespace timor\services\workerman\services;

use Channel\Server as ChannelServer;
use think\worker\Server;

class ChannelService extends Server
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
        $this->config = config('worker_server.channel');
        
        // 实例化一个\Channel\Server服务端做为内部通讯服务
        $this->worker = new ChannelServer($this->config['host'], $this->config['port']);
    }
}