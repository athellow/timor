<?php
declare (strict_types = 1);

namespace app\api\middleware;

use app\Request;
use app\services\user\AuthService;
use think\facade\Config;

class Auth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure       $next
     * @return mixed|void
     */
    public function handle(Request $request, \Closure $next)
    {
        $token = trim(ltrim($request->header(Config::get('jwt.token_name', 'Authori-zation'), ''), 'Bearer'));
        
        /** @var AuthService $service */
        $service = app()->make(AuthService::class);
        $userInfo = $service->checkToken($token);

        Request::macro('user', function (string $key = null) use (&$userInfo) {
            return $key ? ($userInfo[$key] ?? '') : $userInfo;
        });

        return $next($request);
    }
}
