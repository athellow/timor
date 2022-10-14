<?php
declare (strict_types = 1);

namespace timor\services\workerman\callback;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

use timor\services\workerman\jobs\Job;

abstract class Callback
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
    protected $users = [];

    /**
     * @var Job
     */
    protected $job;

    /**
     * @var int
     */
    protected $timer;
    
    public function setUser(TcpConnection $connection)
    {
        $this->users[$connection->userInfo['id']] = $connection;
    }

    public function getUser($uid = null)
    {
        return $uid 
            ? ($this->users[$uid] ?? 0) 
            : $this->users;
    }

    public function job()
    {
        return $this->job;
    }

    /**
     * Worker子进程启动时的回调函数，每个子进程启动时都会执行，总共会运行$worker->count次。
     * 
     * @access public
     * @param  Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
    }

    /**
     * 客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数，每个连接只会触发一次。
     * 
     * @access public
     * @param  TcpConnection $connection 连接对象，TcpConnection实例
     */
    public function onConnect(TcpConnection $connection)
    {
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
    }

    /**
     * 当客户端连接与Workerman断开时触发的回调函数
     * 
     * @access public
     * @param  TcpConnection $connection
     */
    public function onClose(TcpConnection $connection)
    {
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
    }

}