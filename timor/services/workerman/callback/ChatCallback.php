<?php
declare (strict_types = 1);

namespace timor\services\workerman\callback;

use Channel\Client;
use think\facade\Log;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

use timor\services\workerman\ChannelClient;
use timor\services\workerman\jobs\ChatJob;

class ChatCallback extends Callback
{
    /**
     * 在线客服
     * @var TcpConnection[]
     */
    protected $kefuUsers = [];

    /**
     * 构造函数
     * 
     * @access public
     * @param  Worker $worker
     */
    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
        $this->job = new ChatJob($this);
    }
    
    /**
     * @inheritdoc
     */
    public function onWorkerStart(Worker $worker)
    {
        Log::debug('[Chat] onWorkerStart, worker_id: '. $worker->id);
        
        // Channel客户端连接到Channel服务端
        ChannelClient::connect();
        // 订阅事件并注册事件处理函数
        Client::on('chat', function ($data) use ($worker) {
            if (!isset($data['action']) || !$data['action']) return;

            if (method_exists($this->job, $data['action'])) {
                call_user_func([$this->job, $data['action']], $data);
            } else {
                // 默认所有用户
                $ids = !empty($data['ids']) ? $data['ids'] : array_keys($this->getUser());
                
                foreach ($ids as $id) {
                    if ($conn = $this->getUser($id))
                        $this->job->send($conn, $data['data'] ?? []);
                }
            }
        });
        ChannelClient::instance()->on($this, 'login');

        // 进程启动后设置一个每10秒运行一次的定时器
        $this->timer = Timer::add(10, function () use (&$worker) {
            $time_now = time();
            foreach ($worker->connections as $connection) {
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if ($time_now - $connection->lastMessageTime > self::HEARTBEAT_TIME) {
                    $this->job->close($connection, ['action' => 'timeout']);
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function onConnect(TcpConnection $connection)
    {
        Log::debug('[Chat] onConnect, conn_id: '. $connection->id);

        $this->connections[$connection->id] = $connection;
        $connection->lastMessageTime = time();
    }

    /**
     * @inheritdoc
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        Log::debug('[Chat] onMessage, connection_id: '. $connection->id .', data: '. $data);

        // 记录上次收到消息的时间
        $connection->lastMessageTime = time();
        // Log::debug([
        //     'server' => 'chat',
        //     'connections lastMessageTime' => $this->connections[$connection->id]->lastMessageTime,
        //     'connection  lastMessageTime' => $connection->lastMessageTime,
        //     'connections userInfo' => $this->connections[$connection->id]->userInfo ?? [],
        //     'connection  userInfo' => $connection->userInfo ?? []
        // ]);
        
        $res = json_decode($data, true);

        if (!$res || !isset($res['action']) || !$res['action'] || $res['action'] == 'ping') {
            return $this->job->send($connection, ['action' => 'ping', 'now' => time()]);
        }
        
        if (method_exists($this->job, $res['action'])) {
            try {
                $res['connection'] = $connection;

                call_user_func([$this->job, $res['action']], $res);
            } catch (\Exception $e) {
                Log::debug($e);
            } catch (\Error $e) {
                Log::debug($e);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function onClose(TcpConnection $connection)
    {
        if (!empty($connection->userInfo['id'])) {
            unset($this->users[$connection->userInfo['id']]);
            Log::debug('[Chat] onClose, unset user conn, uid: ' . $connection->userInfo['id']);
        }

        if (isset($connection->kefuUserInfo['id'])) {
            unset($this->kefuUsers[$connection->kefuUserInfo['id']]);
            Log::debug('[Chat] onClose, unset kefuUser conn, uid: ' . $connection->kefuUserInfo['id']);
        }

        unset($this->connections[$connection->id]);
        Log::debug('[Chat] onClose, unset connections conn, id: '. $connection->id);
    }

    /**
     * @inheritdoc
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
        Log::debug('[Chat] onError, connection_id: '. $connection->id .', code: '. $code . ', msg: '. $msg);

        echo "error [ $code ] $msg\n";
    }

}