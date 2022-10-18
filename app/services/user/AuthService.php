<?php
declare (strict_types = 1);

namespace app\services\user;

use timor\services\JwtService;

class AuthService
{
    /**
     * 构造方法
    
     * @access public
     */
    public function __construct()
    {
        
    }

    /**
     * 认证TOKEN
    
     * @access public
     * @param $token
     * @return bool|string|array
     * @throws Exception
     */
    public function checkToken($token)
    {
        /** @var JwtService $service */
        $service = app()->make(JwtService::class);
        
        // 创建token
        // print_r($service->getToken(1, 'api'));exit;
        // 设置需要重新登录
        // print_r($service->setLoginAgain('3c95811d6c45186b6b93bd78ff99fd942c34fd99'));exit;
        // print_r($service->clearLoginAgain('3c95811d6c45186b6b93bd78ff99fd942c34fd99'));exit;

        // 刷新token
         $refresh_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0aW1vci5jb20iLCJhdWQiOiJ0aW1vci5jb20iLCJpYXQiOjE2NjYwODQ2NDIsIm5iZiI6MTY2NjA4NDY0MiwiZXhwIjoxNjY3MzgwNjQyLCJ1aWQiOjEsInR5cGUiOiJhcGkiLCJqdGkiOiIzYzk1ODExZDZjNDUxODZiNmI5M2JkNzhmZjk5ZmQ5NDJjMzRmZDk5In0.zir3mzFUG12blWgfiHCUCN5z2W9B9YqUcqC0AEcgwiM';
        // print_r($service->refreshToken($refresh_token));exit;
        // 
        // print_r($service->delRefreshBlacklist('3c95811d6c45186b6b93bd78ff99fd942c34fd99'));exit;
        //设置解析token
        $payload = $service->checkToken($token);
        // print_r($payload);exit;
        return $payload;
    }
}