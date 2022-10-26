<?php
declare (strict_types = 1);

namespace timor\services\pay;

abstract class BasePay
{
    const JSAPI = 'JSAPI';
    const NATIVE = 'NATIVE';
    const APP = 'APP';
    const H5 = 'H5';
    /**
     * @var string
     */
    protected $payType;

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * BasePay constructor
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        $config && $this->config = array_merge($this->config, $config);
        $this->initialize();
    }

    /**
     * 初始化
     * @return mixed
     */
    protected function initialize()
    {}

    /**
     * 设置支付类型
     * @param string $type
     * @return $this
     */
    public function setPayType(string $type)
    {
        $this->payType = $type;
        return $this;
    }

    /**
     * 设置支付类型
     * @param string $type
     * @return $this
     */
    public function authSetPayType()
    {
        if (!$this->payType) {
            // if (request()->isPc()) {
            //     $this->payType = Order::NATIVE;
            // }
            // if (request()->isApp()) {
            //     $this->payType = Order::APP;
            // }
            // if (request()->isRoutine() || request()->isWechat()) {
            //     $this->payType = Order::JSAPI;
            // }
            // if (request()->isH5()) {
            //     $this->payType = 'h5';
            // }

            $this->payType = 'NATIVE';
        }
    }


}
