<?php
declare (strict_types = 1);

namespace app\api\controller;

use app\Request;
use app\services\user\AuthService;
use app\validate\user\UserValidate;

class Auth
{
    protected $services;

    /**
     * 构造方法
     * 
     * @param AuthService $services
     */
    public function __construct(AuthService $services)
    {
        $this->services = $services;
    }

    /**
     * 登录
     * 
     * @access public
     * @param  bool|string|array param 参数
     * @return bool|string|array
     * @throws Exception
     */
    public function login(Request $request, UserValidate $validate)
    {
        $param = $request->param();

        $check = $validate->scene('api_login')->check($param);
        if (!$check) {
            return api_error_client($validate->getError());
        }

        $username = $param['username'];
        $password = $param['password'];
        
        return api_success($this->services->login($username, $password));
    }
}