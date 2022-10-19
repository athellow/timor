<?php
declare (strict_types = 1);

namespace app\api;

use think\exception\Handle;
use think\exception\HttpResponseException;
use think\Response;
use Throwable;
use timor\exception\ApiException;
use timor\exception\AuthException;

class ExceptionHandle extends Handle
{
    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $data = app()->isDebug() ? [
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'previous'  => $e->getPrevious(),
            'trace'     => $e->getTrace()
        ] : [];

        if ($e instanceof HttpResponseException) {
            return parent::render($request, $e);
        } elseif ($e instanceof AuthException || $e instanceof ApiException) {
            return api_error($e->getMessage(), $data, $e->getCode() ?: 400);
        } else {
            $data = app()->isDebug() ? array_merge([
                'code'      => $e->getCode(),
                'msg'       => $e->getMessage(),
            ], $data) : [];

            return api_error('系统错误', $data);
        }
    }
}