<?php


namespace BaiMuZe\Admin\core;


use BaiMuZe\Admin\library\AppServer;
use BaiMuZe\Admin\library\BaseServer;
use BaiMuZe\Admin\library\helper\Helper;
use BaiMuZe\Admin\library\RouterServer;
use BaiMuZe\Admin\utility\Url;
use think\App;
use think\Container;
use think\db\Query;
use think\facade\Log;
use think\Model;

class Application extends BaseServer
{

    /**
     * 系统服务初始化
     * @return void
     */
    protected function initialize()
    {
        static::init($this->app);
    }

    /**
     * 系统服务初始化
     * @param ?\think\App $app
     * @return App
     */
    private static function init(?App $app = null): App
    {
        //替换thinkphp地址初始化运行环境
        AppServer::$sapp = $app ?: Container::getInstance()->make(App::class);
        AppServer::$sapp->bind('url', Url::class);//url服务
        return AppServer::$sapp->debug(static::isDebug());
    }

    /**
     * 初始化并运行主程序
     * @param ?\think\App $app
     */
    public static function doWebsiteInit(?App $app = null)
    {
        $http = static::init($app)->http;
        //启动与初始化服务
//        (new AppServer(AppServer::$sapp))->boot();
//        (new AppServer(AppServer::$sapp))->register();
        //路由实例化
        $route = new RouterServer();
        $route->dispatch(AppServer::$sapp->request);
        ($response = $http->run())->send();
        $http->end($response);
    }

    /**
     * 初始化命令行
     * @param ?\think\App $app
     * @return integer
     */
    public static function doConsoleInit(?App $app = null): int
    {
        try {
            //启动与初始化服务
//            (new AppServer(AppServer::$sapp))->boot();
//            (new AppServer(AppServer::$sapp))->register();
            return static::init($app)->console->run();
        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
            return 0;
        }
    }

    /**
     * 数据增量保存
     * @param Model|Query|string $query 数据查询对象
     * @param array $data 需要保存的数据，成功返回对应模型
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return boolean|integer 失败返回 false, 成功返回主键值或 true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function save($query, array &$data, string $key = 'id', $map = [])
    {
        $query = Helper::CreateQuery($query)->master()->strict(false);
        if (empty($map[$key])) $query->where([$key => $data[$key] ?? null]);
        $model = $query->where($map)->findOrEmpty();
        // 当前操作方法描述
        $action = $model->isExists() ? 'onAdminUpdate' : 'onAdminInsert';
        // 写入或更新模型数据
        if ($model->save($data) === false) return false;
        // 模型自定义事件回调
        if ($model instanceof \BaiMuZe\core\Model) {
            $model->$action(strval($model[$key] ?? ''));
        }
        $data = $model->toArray();
        return $model[$key] ?? true;
    }

    /**
     * 是否为开发模式运行
     * @return boolean
     */
    public static function isDebug(): bool
    {
        return static::getRuntime('mode') !== 'product';
    }

    /**
     * 获取实时运行配置
     * @author 白沐泽
     * @createdate 2022-11-22
     */
    public static function getRuntime(?string $name = null, array $default = [])
    {
        $env = AppServer::$sapp->getRootPath() . 'runtime/app/.env';
        if (file_exists($env)) AppServer::$sapp->env->load($env);
        $data = [
            'mode' => AppServer::$sapp->env->get('RUNTIME_MODE') ?: 'debug',
            'appmap' => AppServer::$sapp->env->get('RUNTIME_APPMAP') ?: [],
            'domain' => AppServer::$sapp->env->get('RUNTIME_DOMAIN') ?: [],
        ];
        return is_null($name) ? $data : ($data[$name] ?? $default);
    }

    /**
     * 获取实时运行环境
     * @author 白沐泽
     * @createdate 2022-11-22
     */
    public static function getOS()
    {
        if (PATH_SEPARATOR == ':' && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return 1;//linux
        }
        return 2;//win
    }
}