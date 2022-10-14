<?php
declare (strict_types = 1);

namespace timor\services\workerman\jobs;

use Workerman\Connection\TcpConnection;

use timor\services\workerman\callback\Callback;

abstract class Job
{
    /**
     * @var Callback
     */
    protected $callback;

    /**
     * 构造函数
     * 
     * @access public
     * @param  Callback $callback
     */
    public function __construct(Callback $callback)
    {
        $this->callback = $callback;
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
     * 关闭连接
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

}