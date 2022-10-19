<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use app\api\middleware\Auth;
use think\facade\Route;

Route::group('v1', function () {
    Route::group(function () {
        Route::post('login', 'Auth/login')->name('login');

        Route::get('hello/[:name]', 'Index/hello');

    })->middleware(Auth::class);

});

