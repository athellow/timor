<?php
declare (strict_types = 1);

namespace app\services\user;

use app\model\user\User;
use Firebase\JWT\ExpiredException;
use think\facade\Event;
use timor\exception\ApiException;
use timor\exception\AuthException;
use timor\services\JwtService;

class AuthService
{
    /**
     * 构造方法
     * 
     * @access public
     * @param $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * 认证TOKEN
     * 
     * @access public
     * @param $token
     * @return bool|string|array
     * @throws ExpiredException
     * @throws AuthException
     */
    public function checkToken($token)
    {
        /** @var JwtService $service */
        $service = app()->make(JwtService::class);
        
        // 设置需要重新登录
        // print_r($service->setLoginAgain('3c95811d6c45186b6b93bd78ff99fd942c34fd99'));exit;
        // print_r($service->clearLoginAgain('3c95811d6c45186b6b93bd78ff99fd942c34fd99'));exit;

        // 刷新token
         $refresh_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0aW1vci5jb20iLCJhdWQiOiJ0aW1vci5jb20iLCJpYXQiOjE2NjYwODQ2NDIsIm5iZiI6MTY2NjA4NDY0MiwiZXhwIjoxNjY3MzgwNjQyLCJ1aWQiOjEsInR5cGUiOiJhcGkiLCJqdGkiOiIzYzk1ODExZDZjNDUxODZiNmI5M2JkNzhmZjk5ZmQ5NDJjMzRmZDk5In0.zir3mzFUG12blWgfiHCUCN5z2W9B9YqUcqC0AEcgwiM';
        // print_r($service->refreshToken($refresh_token));exit;
        // 
        // print_r($service->delRefreshBlacklist('3c95811d6c45186b6b93bd78ff99fd942c34fd99'));exit;
        //设置解析token
        try{
            $payload = $service->checkToken($token);
        } catch (ExpiredException $e) {
            throw new AuthException($e->getMessage());
        } catch (\Throwable $e) {
            throw new AuthException($e->getMessage());
        }
        
        $user = $this->model->find($payload->uid);

        return $user;
    }

    /**
     * 登录
     *
     * @access public
     * @param  string param 参数
     * @param  string param 参数
     * @return array
     * @throws ApiException
     */
    public function login($username, $password): array
    {
        $user = $this->model->where('username', '=', $username)->findOrEmpty();

        if ($user->isEmpty()) {
            throw new ApiException('用户不存在');
        }

        $verify = password_verify($password, base64_decode($user->password));
        if (!$verify) {
            throw new ApiException('密码错误');
        }

        // 检查是否被冻结
        if ($user->status !== 1) {
            throw new ApiException('账号被冻结');
        }

        // 用户登录事件
        // Event::trigger('UserLogin', $user);
        // TaskJob::dispatchDo('emptyYesterdayAttachment');
        
        /** @var JwtService $service */
        $service = app()->make(JwtService::class);
        
        $token = $service->getToken($user->id, 'api');

        $data = [
            'token' => $token['token'],
            'token_exp_time' => $token['params']['exp']
        ];

        if ($service->isEnableRefreshToken()) {
            $refresh_token = $service->getRefreshToken($user->id, 'api');

            $data['refresh_token'] = $refresh_token['token'];
            $data['refresh_token_exp_time'] = $refresh_token['params']['exp'];
        }

        return $data;
    }
}