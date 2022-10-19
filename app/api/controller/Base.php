<?php
declare (strict_types = 1);

namespace app\api\controller;

use think\App;
use think\response\Json;
use think\Validate;

/**
 * API控制器基础类
 */
class Base
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 当前请求参数
     * @var array
     */
    protected array $param;

    /**
     * 当前页数
     * @var int 
     */
    protected int $page;

    /**
     * 当前每页数量
     * @var int
     */
    protected int $limit;

    /**
     * 当前访问的url
     * @var string 
     */
    protected string $url;

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        $this->initData();
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initData()
    {
        $this->param = (array)request()->param();
        // 初始化基本数据
        $this->page  = (int)($this->param['page'] ?? 1);
        $this->limit = (int)($this->param['limit'] ?? 10);
        // 限制每页数量最大为100条
        $this->limit = $this->limit > 100 ? 100 : $this->limit;
        
        $this->url = parse_name(app('http')->getName())
            . '/' . parse_name($this->request->controller())
            . '/' . parse_name($this->request->action());
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /** 访问不存在的方法 */
    public function __call($name, $arguments): Json
    {
        return api_error_404();
    }

}