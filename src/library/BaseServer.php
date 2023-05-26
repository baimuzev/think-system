<?php


namespace BaiMuZe\Admin\library;

use think\App;
use think\Container;

/**
 * 自定义服务基类
 * Class Service
 * @author 白沐泽
 * @createdate 2022-11-22
 */
abstract class BaseServer
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * Service constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    /**
     * 初始化服务
     */
    protected function initialize()
    {
        //启动与初始化服务
    }

    /**
     * 静态实例对象
     * @param array $var 实例参数
     * @param boolean $new 创建新实例
     * @return static|mixed
     */
    public static function instance(array $var = [], bool $new = false)
    {
        return Container::getInstance()->make(static::class, $var, $new);
    }
}