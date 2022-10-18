<?php

// +----------------------------------------------------------------------
// | JWT设置
// +----------------------------------------------------------------------

return [
    // TOKEN 字段名
    'token_name' => env('api.token_name', 'Authori-zation'),

    'jwt_key' => 'b1816er1a7egh5k5hty78bkl12gg612u3zbwtip9',
    'jwt_exp' => 1296000,
    'refresh_token_exp' => 2592000,
    'enable_refresh_token' => true,
    'reuse_check' => true,
];