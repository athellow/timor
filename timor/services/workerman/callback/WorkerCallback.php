<?php
declare (strict_types = 1);

namespace timor\services\workerman\callback;

use think\facade\Log;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

use timor\services\workerman\ChannelClient;
use timor\services\workerman\jobs\WorkerJob;

class WorkerCallback extends Callback
{
    /**
     * 构造函数
     * 
     * @access public
     * @param  Worker $worker
     */
    public function __construct(Worker $worker)
    {
        $this->worker = $worker;
        $this->job = new WorkerJob($this);
    }
    
    /**
     * @inheritdoc
     */
    public function onWorkerStart(Worker $worker)
    {
        Log::debug('[Worker] onWorkerStart, worker_id: '. $worker->id);

        // 订阅事件并注册事件处理函数
        ChannelClient::instance()->on($this, 'timor');
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
        Log::debug('[Worker] onConnect, conn_id: '. $connection->id);

        $this->connections[$connection->id] = $connection;
        $connection->lastMessageTime = time();
    }

    /**
     * @inheritdoc
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        Log::debug('[Worker] onMessage, connection_id: '. $connection->id .', data: '. $data);

        // 记录上次收到消息的时间
        $connection->lastMessageTime = time();
        // Log::debug([
        //     'service' => 'Worker',
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
            unset($this->user[$connection->userInfo['id']]);
            Log::debug('[Worker] onClose, unset user conn, uid: ' . $connection->userInfo['id']);
        }
        
        unset($this->connections[$connection->id]);
        Log::debug('[Worker] onClose, unset connections conn, id: '. $connection->id);
    }

    /**
     * @inheritdoc
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
        Log::debug('[Worker] onError, connection_id: '. $connection->id .', code: '. $code . ', msg: '. $msg);

        echo "error [ $code ] $msg\n";
    }

}