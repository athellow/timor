<?php
declare (strict_types = 1);

namespace app\validate\user;

use app\validate\BaseValidate;

class UserValidate extends BaseValidate
{
    protected $rule = [
        'username|账号'        => 'require',
        'password|密码'        => 'require',

    ];

    protected $message = [
        'username.required'      => '账号不能为空',
        'password.required'      => '密码不能为空',

    ];

    protected $scene = [
        'api_login'     => ['username', 'password'],
    ];
}