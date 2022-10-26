<?php
declare (strict_types = 1);

namespace timor\services\pay\support;

use Alipay\EasySDK\Kernel\Config;
use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Util\ResponseChecker;
use Alipay\EasySDK\Payment\Common\Models\AlipayTradeFastpayRefundQueryResponse;
use Alipay\EasySDK\Payment\Common\Models\AlipayTradeRefundResponse;
use Alipay\EasySDK\Payment\Wap\Models\AlipayTradeWapPayResponse;
use timor\exception\PayException;
use timor\services\pay\BasePay;
use timor\services\pay\PayInterface;
use timor\services\AliPayService;

class AliPay extends BasePay implements PayInterface
{
    /**
     * 配置
     * @var array
     */
    protected $config = [
        'appId' => '',
        'merchantPrivateKey' => '', // 应用私钥
        'alipayPublicKey' => '',    // 支付宝公钥
        'notifyUrl' => '',          // 可设置异步通知接收服务地址
        'encryptKey' => '',         // 可设置AES密钥，调用AES加解密相关接口时需要（可选）
    ];

    /**
     * @var ResponseChecker
     */
    protected $response;

    /**
     * 初始化
     * @return void
     */
    protected function initialize()
    {
        if (empty($this->config)) {
            $this->config = [
                'appId' => 'xxxxxxx',
                'merchantPrivateKey' => 'xxxxxxx',  // 应用私钥
                'alipayPublicKey' => 'xxxxxxx',     // 支付宝公钥
                'notifyUrl' => 'xxxxxxx',           // 可设置异步通知接收服务地址（可选）
                'encryptKey' => '',                 // 可设置AES密钥，调用AES加解密相关接口时需要（可选）
            ];
        }
        
        Factory::setOptions($this->getOptions());

        $this->response = new ResponseChecker();
    }

    /**
     * 设置配置
     * @return Config
     */
    protected function getOptions()
    {
        return new Config(array_merge([
            'protocol' => 'https',
            'gatewayHost' => 'openapi.alipay.com',
            'signType' => 'RSA2',
        ], $this->config));
    }

    /**
     * @inheritdoc
     * @return AlipayTradeWapPayResponse|mixed
     * @throws PayException
     */
    public function create(string $orderId, string $totalFee, string $attach, string $body, string $detail, array $options = [])
    {
        $this->authSetPayType();

        $title = trim($body);

        try {
            switch ($this->payType) {
                case self::NATIVE:
                    // 二维码支付
                    $result = Factory::payment()->faceToFace()->optional('passback_params', $attach)->precreate($title, $orderId, $totalFee);
                    
                    break;
                case self::APP:
                    $result = Factory::payment()->app()->optional('passback_params', $attach)->pay($title, $orderId, $totalFee);
    
                    break;
                case self::H5:
                    $result = Factory::payment()->wap()->optional('passback_params', $attach)->pay($title, $orderId, $totalFee, $options['uitUrl'] ?? '', $options['siteUrl'] ?? '');
                    
                    break;
                default:
                    throw new PayException('支付宝支付：支付类型错误');
            }

            if ($this->response->success($result)) {
                return $result->body ?? $result;
            } else {
                throw new PayException('失败原因:' . $result->msg . ',' . $result->subMsg);
            }
        } catch (\Exception $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     * @return AlipayTradeRefundResponse|mixed
     */
    public function refund(string $outTradeNo, array $options = [])
    {
        try {
            $result = Factory::payment()->common()->refund($outTradeNo, $options['totalAmount'], $options['refund_id']);
            if ($this->response->success($result)) {
                return $result;
            } else {
                throw new PayException('失败原因:' . $result->msg . ',' . $result->subMsg);
            }
        } catch (\Exception $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     * @return AlipayTradeFastpayRefundQueryResponse|mixed
     */
    public function queryRefund(string $outTradeNo, string $outRequestNo, array $other = [])
    {
        try {
            $result = Factory::payment()->common()->queryRefund($outTradeNo, $outRequestNo);
            if ($this->response->success($result)) {
                return $result;
            } else {
                throw new PayException('失败原因:' . $result->msg . ',' . $result->subMsg);
            }
        } catch (\Exception $e) {
            throw new PayException($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     * @return mixed|string
     */
    public function notify()
    {
        
    }
}
