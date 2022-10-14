<?php
declare (strict_types = 1);

namespace timor\services\workerman\jobs;

use timor\services\workerman\ChannelClient;

class WorkerJob extends Job
{
    /**
     * 认证
     * 
     * @access public
     * @param  array $data
     * @return bool|null
     */
    public function login(array $data)
    {
        $connection = $data['connection'];

        if (!isset($data['data']) || !$token = $data['data']) {
            return $this->close($connection, ['action' => 'error', 'msg' => '授权失败!']);
        }

        try {
            // TODO 认证$token 获取用户信息
            $userInfo = ['id' => 1, 'name' => 'superman'];
        } catch (\Exception $e) {
            return $this->close($connection, [
                'action' => 'error',
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }

        if (empty($userInfo)) {
            return $this->close($connection, ['action' => 'error', 'msg' => '授权失败!']);
        }

        $connection->userInfo = $userInfo;
        
        $this->callback->setUser($connection);

        // 授权成功实践事件
        ChannelClient::instance()->setEventName('login')->publish('login', [], [$userInfo['id']]);

        return $this->send($connection, ['action' => 'success', 'msg' => '授权成功!']);
    }
}