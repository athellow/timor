<?php
declare (strict_types = 1);

namespace timor\services\workerman;

use Channel\Client;
use timor\services\workerman\callback\Callback;

class ChannelClient
{
    /**
     * @var ChannelClient
     */
    protected static $instance;

    /**
     * @var
     */
    protected $eventName = 'timor';

    /**
     * 构造函数
     * 
     * @access public
     */
    public function __construct()
    {
        self::connect();
    }

    /**
     * 创建实例
     * 
     * @access public
     * @return ChannelClient
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 连接Channel/Server
     * 
     * @access public
     */
    public static function connect()
    {
        $config = config('worker_server.channel');
        // Channel/Server 监听的ip地址 和 端口
        Client::connect($config['host'], $config['port']);
    }

    /**
     * 设置事件名称
     * 
     * @access public
     * @param string $name
     * @return $this
     */
    public function setEventName(string $name)
    {
        $this->eventName = $name;
        return $this;
    }

    /**
     * 订阅事件并注册事件发生时的回调
     * 
     * @see https://www.workerman.net/doc/workerman/components/channel-client-on.html
     * @access public
     * @param  Callback $callback
     * @param  string $name 事件名称
     */
    public function on(Callback $callback, string $name)
    {
        Client::on($name, function ($event_data) use ($callback) {
            if (!isset($event_data['action']) || !$event_data['action']) return;
            // 默认所有用户
            $ids = !empty($event_data['ids']) ? $event_data['ids'] : array_keys($callback->getUser());
            
            foreach ($ids as $id) {
                if ($conn = $callback->getUser($id))
                    $callback->job()->send($conn, $event_data['data'] ?? []);
            }
        });
    }
    
    /**
     * 发布某个事件
     * 
     * @access public
     * @see https://www.workerman.net/doc/workerman/components/channel-client-publish.html
     * @param string $action 类型
     * @param array $data 数据
     * @param array $ids 用户 id，默认全部
     */
    public function publish(string $action, ?array $data = [], ?array $ids = [])
    {
        Client::publish($this->eventName, [
            'action' => $action,
            'data' => $data,
            'ids' => $ids
        ]);
        
        $this->eventName = 'timor';
    }
}