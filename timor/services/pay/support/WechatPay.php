<?php
declare (strict_types = 1);

namespace timor\services\pay\support;

use EasyWeChat\Pay\Application;

use timor\services\pay\BasePay;
use timor\services\pay\PayInterface;
use timor\exception\PayException;

class WechatPay extends BasePay implements PayInterface
{
    // jsapi支付接口
    const API_JSAPI_URL = 'v3/pay/transactions/jsapi';

    // 二维码支付
    const API_NATIVE_URL = 'v3/pay/transactions/native';

    // app支付
    const API_APP_URL = 'v3/pay/transactions/app';

    // h5支付接口
    const API_H5_URL = 'v3/pay/transactions/h5';

    // 发起商家转账API
    const API_BATCHES_URL = 'v3/transfer/batches';

    // 退款
    const API_REFUND_URL = 'v3/refund/domestic/refunds';

    // 退款查询接口
    const API_REFUND_QUERY_URL = 'v3/refund/domestic/refunds/{out_refund_no}';

    /**
     * @var Application
     */
    protected $application;

    /**
     * 初始化
     * @return mixed
     */
    protected function initialize()
    {
        if (empty($this->config)) {
            $this->config = [
                'mch_id' => 1360649000,
            
                // 商户证书
                'private_key' => __DIR__ . '/certs/apiclient_key.pem',
                'certificate' => __DIR__ . '/certs/apiclient_cert.pem',
            
                // v3 API 秘钥
                'secret_key' => '43A03299A3C3FED3D8CE7B820Fxxxxx',
                // v2 API 秘钥
                'v2_secret_key' => '26db3e15cfedb44abfbb5fe94fxxxxx',
                
                // 平台证书：微信支付 APIv3 平台证书，需要使用工具下载
                // 下载工具：https://github.com/wechatpay-apiv3/CertificateDownloader
                'platform_certs' => [
                    // '/path/to/wechatpay/cert.pem',
                ],
            
                /**
                 * 接口请求相关配置，超时时间等，具体可用参数请参考：
                 * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
                 */
                'http' => [
                    'throw'  => true, // 状态码非 200、300 时是否抛出异常，默认为开启
                    'timeout' => 5.0,
                    // 'base_uri' => 'https://api.mch.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
                ],
            ];
        }
        
        $this->application = new Application($this->config);
    }

    /**
     * @inheritdoc
     * @throws PayException
     */
    public function create(string $orderId, string $totalFee, string $attach, string $body, string $detail, array $options = [])
    {
        $this->authSetPayType();

        $options = [
            'mchid' => (string)$this->application->getMerchant()->getMerchantId(),
            'out_trade_no' => 'native20210720xxx',
            'description' => 'Image形象店-深圳腾大-QQ公仔',
            'notify_url' => 'https://weixin.qq.com/',
            'amount' => [
                'total' => 1,
                'currency' => 'CNY',
            ]
        ];

        switch ($this->payType) {
            case self::JSAPI:
                $url = self::API_JSAPI_URL;

                $options['appid'] = 'wxe2fb06xxxxxxxxxx6';
                $options['payer'] = [
                    "openid" => "o4GgauInH_RCEdvrrNGrnxxxxxx" // <---- 请修改为服务号下单用户的 openid
                ];

                break;
            case self::NATIVE:
                $url = self::API_NATIVE_URL;

                $options['appid'] = 'wxe2fb06xxxxxxxxxx6';
                
                break;
            case self::APP:
                $url = self::API_APP_URL;

                break;
            case self::H5:
                $url = self::API_H5_URL;
                
                break;
            default:
                throw new PayException('微信支付：支付类型错误');
        }

        $response = $this->application->getClient()->postJson($url, $options);
        print_r($response->toArray(false));
    }

    /**
     * @inheritdoc
     */
    public function refund(string $outTradeNo, array $opt = [])
    {
        
    }

    /**
     * @inheritdoc
     */
    public function queryRefund(string $outTradeNo, string $outRequestNo, array $other = [])
    {
        $outTradeNo = 'native20210720xxx';
        $response = $this->application->getClient()->get("v3/pay/transactions/out-trade-no/{$outTradeNo}", [
            'query'=>[
                'mchid' =>  $this->application->getMerchant()->getMerchantId()
            ]
        ]);

        print_r($response->toArray());

        return $response->toArray();
    }

    /**
     * @inheritdoc
     */
    public function notify()
    {
        
    }
}
