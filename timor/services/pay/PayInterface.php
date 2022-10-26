<?php
declare (strict_types = 1);

namespace timor\services\pay;

interface PayInterface
{
    /**
     * 设置支付类型
     * @param string $type 支付类型
     * @return $this
     */
    public function setPayType(string $type);

    /**
     * 创建支付
     * @param string $orderId 订单号
     * @param string $totalFee 支付金额
     * @param string $attach 回调内容
     * @param string $body 支付body
     * @param string $detail 详情
     * @param array $options 其他参数
     * @return mixed
     */
    public function create(string $orderId, string $totalFee, string $attach, string $body, string $detail, array $options = []);

    /**
     * 退款
     * @param string $outTradeNo 退款单号
     * @param array $options 其他参数
     * @return mixed
     */
    public function refund(string $outTradeNo, array $options = []);

    /**
     * 查询订单
     * @param string $outTradeNo 退款单号
     * @param string $outRequestNo 支付商户单号
     * @param array $other 其他参数
     * @return mixed
     */
    public function queryRefund(string $outTradeNo, string $outRequestNo, array $other = []);

    /**
     * 支付回调
     * @return mixed
     */
    public function notify();

}
