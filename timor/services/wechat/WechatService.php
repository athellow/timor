<?php
declare (strict_types = 1);

namespace timor\services\wechat;

use EasyWeChat\OfficialAccount\Application;

class WechatService
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * WechatService constructor
     * @access public
     * @param  array $config 配置
     */
    public function __construct(array $config = [])
    {
        $this->application = new Application($config ?: config('wechat'));

        $this->initialize();
    }

    /**
     * 初始化
     * @access protected
     */
    protected function initialize()
    {
    }


}