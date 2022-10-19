<?php
declare (strict_types = 1);

namespace timor\services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Cache;
use timor\Exception\AuthException;

class JwtService
{
    /**
     * @var string
     */
    protected $key = 'b1816er1a7egh5k5hty78bkl12gg612u3zbwtip9';
    /**
     * @var int 过期时间秒数，默认15天
     */
    protected int $exp = 1296000;

    /**
     * @var int 刷新token 默认为30天
     */
    protected int $refreshTokenExp = 2592000;

    /**
     * @var bool 开启token刷新
     */
    protected bool $enableRefreshToken = false;

    /**
     * @var bool 重复使用检测
     */
    protected bool $reuseCheck = false;

    /**
     * @var string 黑名单缓存前缀
     */
    protected string $refreshTokenBlacklistKeyPrefix = 'access_token_blacklist_';

    /**
     * @var string 需要再次登录标识前缀前缀
     */
    protected string $loginAgainKeyPrefix = 'user_login_again_';

    /**
     * 构造方法
     * 
     * @access public
     */
    public function __construct()
    {
        $config = config('jwt');

        $this->key                = $config['jwt_key'] ?? $this->key;
        $this->exp                = $config['jwt_exp'] ?? $this->exp;
        $this->refreshTokenExp    = $config['refresh_token_exp'] ?? $this->refreshTokenExp;
        $this->enableRefreshToken = $config['enable_refresh_token'] ?? $this->enableRefreshToken;
        $this->reuseCheck         = $config['reuse_check'] ?? $this->reuseCheck;
    }
    
    /**
     * 获取token
     * @access public
     * @param int $uid
     * @param string $type
     * @param array $params
     * @return array
     */
    public function getToken(int $uid, string $type, array $params = []): array
    {
        $host = app()->request->host();
        $time = time();
        
        $params = array_merge([
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,         // 签发时间
            'nbf' => $time,         // (Not Before)生效时间，某个时间点后才能访问
            'exp' => $time + $this->exp,
            'uid' => $uid,
            'type' => $type,
        ], $params);

        $params['jti'] = $this->createJti($type . '_' . $uid);
        $token = JWT::encode($params, $this->key, 'HS256');

        return compact('token', 'params');
    }

    /**
     * 获取refresh token
     *
     * @access public
     * @param int $uid
     * @param string $type
     * @param array $params
     * @return array
     */
    public function getRefreshToken(int $uid, string $type)
    {
        $time = time();

        return $this->getToken($uid, $type, [
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + $this->refreshTokenExp
        ]);
    }

    /**
     * 验证token
     * 
     * @access public
     * @param string $token
     * @return object
     * @throws AuthException
     */
    public function checkToken($token)
    {
        try {
            JWT::$leeway = 60;
            $payload = JWT::decode($token, new Key($this->key, 'HS256'));

            // 开启了刷新token和重复使用检查，判断是否需要重新登录
            if ($this->enableRefreshToken && $this->reuseCheck && $this->needLoginAgain($payload)) {
                throw  new AuthException('需重新登录');
            }

            return $payload;
        } catch (\Exception $e) {
            throw  new AuthException($e->getMessage());
        }
    }

    /**
     * @param $refresh_token
     * @return array
     * @throws AuthException
     */
    public function refreshToken($refresh_token): array
    {
        if (!$this->enableRefreshToken) {
            throw new AuthException('未开启token刷新功能');
        }

        if ($this->exp >= $this->refreshTokenExp) {
            throw new AuthException('access_token有效期配置超过refresh_token');
        }

        // 检查token的合法性
        $payload = $this->checkToken($refresh_token);
        $jti = $payload->jti ?? '';
        $uid = $payload->uid ?? 0;
        $type = $payload->type ?? '';

        // 如果开启了重复使用检查
        if ($this->reuseCheck) {
            $used = $this->isRefreshTokenBlacklist($jti);
            if ($used) {
                // 如果此refresh_token已经被使用过了,此用户必须重新登录，
                $this->setLoginAgain($jti);
                throw new AuthException('refresh_token被重复使用');
            } else {
                $this->addRefreshBlacklist($payload);
            }
        }

        return [
            'access_token'  => $this->getToken($uid, $type),
            'refresh_token' => $this->getRefreshToken($uid, $type),
        ];
    }

    /**
     * 检查jti是否在黑名单
     * 
     * @access public
     * @param string $jti
     * @return bool
     */
    public function isRefreshTokenBlacklist($jti): bool
    {
        $blacklist_key = $this->refreshTokenBlacklistKeyPrefix . $jti;

        return Cache::has($blacklist_key);
    }

    /**
     * 将jti加入黑名单
     * 
     * @access public
     * @param object $payload
     * @return bool
     */
    public function addRefreshBlacklist($payload): bool
    {
        $jti = $payload->jti ?? '';
        $uid = $payload->uid ?? 0;
        $type = $payload->type ?? '';

        $time          = time();
        $blacklist_key = $this->refreshTokenBlacklistKeyPrefix . $jti;
        $value         = [
            'time' => $time,
            'uid'  => $uid,
            'type' => $type
        ];

        return Cache::set($blacklist_key, $value, $payload->exp - $time + 1);
    }

    /**
     * 从黑名单里删除token
     * 
     * @access public
     * @param string $jti
     * @return bool
     */
    public function delRefreshBlacklist($jti): bool
    {
        $blacklist_key = $this->refreshTokenBlacklistKeyPrefix . $jti;

        return Cache::delete($blacklist_key);
    }

    /**
     * 检查是否需要重新登录
     * 
     * @access public
     * @param object $payload
     * @return bool
     */
    public function needLoginAgain($payload): bool
    {
        $jti = $payload->jti ?? '';
        
        $login_again_key = $this->loginAgainKeyPrefix . $jti;

        if (Cache::has($login_again_key)) {
            $time = Cache::get($login_again_key);
            $iat  = $payload->iat ?? 0;
            // 如果当前token签发时间早于重用记录时间，证明token已失效
            if ($iat <= $time) {
                return true;
            }
        }

        return false;
    }

    /**
     * 设置用户必须重新登录，添加1秒的防护机制
     * 
     * @access public
     * @param string $jti
     * @return bool
     */
    public function setLoginAgain($jti): bool
    {
        $login_again_key = $this->loginAgainKeyPrefix . $jti;

        return Cache::set($login_again_key, time() + 1, $this->refreshTokenExp + 1);
    }

    /**
     * 清除需要重新登录的标记
     * 
     * @access public
     * @param string $jti
     * @return bool
     */
    public function clearLoginAgain($jti): bool
    {
        $login_again_key = $this->loginAgainKeyPrefix . $jti;

        return Cache::delete($login_again_key);
    }

    /**
     * 创建jti
     * 
     * @access public
     * @param string $key
     * @return string
     */
    public function createJti($key): string
    {
        $time  = explode(' ', microtime());
        $micro = substr($time[0], 2, 3);

        return sha1($key . $time[1] . $micro . uniqid('jwt_' . $key, true));
    }

    /**
     * 是否开启token刷新
     *
     * @access public
     * @return bool
     */
    public function isEnableRefreshToken()
    {
        return $this->enableRefreshToken;
    }
}