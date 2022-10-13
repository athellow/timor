<?php
declare (strict_types = 1);

namespace timor\services\workerman;

use Channel\Client;
use Exception;
use think\facade\Log;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

class WorkermanService
{
    const HEARTBEAT_TIME = 55;

    /**
     * @var Worker
     */
    protected $worker;

    /**
     * @var TcpConnection[]
     */
    protected $connections = [];

    /**
     * @var TcpConnection[]
     */
    protected $user = [];

    /**
     * @var int
     */
    protected $timer;

    /**
     * 构造函数
     * 
     * @access public
     * @param  Worker $worker
     */
    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
    }
    
    /**
     * Worker子进程启动时的回调函数，每个子进程启动时都会执行，总共会运行$worker->count次。
     * 
     * @access public
     * @param  Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
        Log::debug(['type' => 'onWorkerStart', 'worker_id' => $worker->id]);

        // 订阅事件并注册事件处理函数
        ChannelClient::instance()->on($this, 'timor');
        ChannelClient::instance()->on($this, 'login');

        // 进程启动后设置一个每10秒运行一次的定时器
        $this->timer = Timer::add(10, function () use (&$worker) {
            $time_now = time();
            foreach ($worker->connections as $connection) {
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastMessageTime > self::HEARTBEAT_TIME) {
                    $this->close($connection, ['type' => 'timeout']);
                }
            }
        });
    }

    /**
     * 客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数，每个连接只会触发一次。
     * 
     * @access public
     * @param  TcpConnection $connection 连接对象，TcpConnection实例
     */
    public function onConnect(TcpConnection $connection)
    {
        Log::debug(['type' => 'onConnect', 'conn_id' => $connection->id]);

        $this->connections[$connection->id] = $connection;
        $connection->lastMessageTime = time();
    }

    /**
     * 客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数
     * 
     * @access public
     * @param  TcpConnection $connection 连接对象，TcpConnection实例
     * @param  string $data 客户端发来的数据
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        Log::debug(['type' => 'onMessage', 'data' => $data]);

        // 记录上次收到消息的时间
        $connection->lastMessageTime = time();
        Log::debug([
            'connections lastMessageTime' => $this->connections[$connection->id]->lastMessageTime,
            'connection  lastMessageTime' => $connection->lastMessageTime,
            'connections userInfo' => $this->connections[$connection->id]->userInfo ?? [],
            'connection  userInfo' => $connection->userInfo ?? []
        ]);
        
        $res = json_decode($data, true);

        if (!$res || !isset($res['type']) || !$res['type'] || $res['type'] == 'ping') {
            return $this->send($connection, ['type' => 'ping', 'now' => time()]);
        }
        
        if (!method_exists($this, $res['type'])) return;

        $this->{$res['type']}($connection, $res + ['data' => []]);
    }

    /**
     * 当客户端连接与Workerman断开时触发的回调函数
     * 
     * @access public
     * @param  TcpConnection $connection
     */
    public function onClose(TcpConnection $connection)
    {
        Log::debug(['type' => 'onClose', 'conn_id' => $connection->id]);

        if (!empty($connection->userInfo['id'])) {
            unset($this->user[$connection->userInfo['id']]);
        }
        
        unset($this->connections[$connection->id]);
    }

    /**
     * 当客户端的连接上发生错误时触发
     * 
     * @see https://www.workerman.net/doc/workerman/worker/on-error.html
     * @access public
     * @param  TcpConnection $connection
     * @param  int $code    错误码
     * @param  string $msg  错误消息
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
        Log::debug(['type' => 'onError', 'conn_id' => $connection->id, 'code' => $code, 'msg' => $msg]);

        echo "error [ $code ] $msg\n";
    }

    /**
     * 发送数据
     * 
     * @access public
     * @param  TcpConnection $connection
     * @param  array $data
     * @return bool|null
     */
    public function send(TcpConnection $connection, ?array $data = [])
    {
        return $connection->send(json_encode($data));
    }

    /**
     * 发送数据
     * 
     * @access public
     * @param  TcpConnection $connection
     * @param  array $data
     * @return void
     */
    public function close(TcpConnection $connection, ?array $data = [])
    {
        return $connection->close(json_encode(array_merge($data, ['close' => true])));
    }

    /**
     * 认证
     * 
     * @access public
     * @param  TcpConnection $connection
     * @param  array $data
     * @return bool|null
     */
    public function login(TcpConnection $connection, array $data)
    {
        if (!isset($data['data']) || !$token = $data['data']) {
            return $this->close($connection, ['type' => 'error', 'msg' => '授权失败!']);
        }

        try {
            // TODO 认证$token 获取用户信息
            $userInfo = ['id' => 1, 'name' => 'superman'];
        } catch (Exception $e) {
            return $this->close($connection, [
                'type' => 'error',
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        if (empty($userInfo)) {
            return $this->close($connection, ['type' => 'error', 'msg' => '授权失败!']);
        }

        $connection->userInfo = $userInfo;
        $this->setUser($connection);

        // 授权成功实践事件
        ChannelClient::instance()->setEventName('login')->publish('login', [], [$userInfo['id']]);

        return $this->send($connection, ['type' => 'success', 'msg' => '授权成功!']);
    }

    
    public function setUser(TcpConnection $connection)
    {
        $this->user[$connection->userInfo['id']] = $connection;
    }

    public function getUser($uid = null)
    {
        return $uid 
            ? ($this->user[$uid] ?? 0) 
            : $this->user;
    }


}