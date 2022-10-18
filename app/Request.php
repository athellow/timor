<?php
namespace app;

use Spatie\Macroable\Macroable;

// 应用请求对象类
class Request extends \think\Request
{
    use Macroable;
}
